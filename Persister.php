<?php

namespace Orm;

use Orm\Entity;
use Orm\Metadata;
use Orm\Database;

class Persister
{
  protected $_metadata = null;

  protected $_entityManager = null;

  protected $_dbApdater = null;

  protected $_entityName = '';

  protected $_tableAliases = array();

  protected $_tableIndex = 0;

  protected $_columnNames = array();

  protected $_columnDataTypes = array();

  protected $_selectColumnSql = '';

  protected $_insertSql = '';

  /**
   * __construct
   *
   * Public constructor
   * 
   * @param Metadata\EntityMetadata $metadata Entity metadata
   * @param EntityManager $em The entity manager instance
   */
  public function __construct(Metadata\EntityMetadata $metadata, EntityManager $em)
  {
    $this->_entityManager = $em;
    $this->_dbApdater       = $em->getDatabaseAdapter();
    $this->_metadata        = $metadata;
    $this->_entityName     = $metadata->getEntityName(); 

    return $this;
  }

  /**
   * getMetadata
   *
   * Return entity metadata
   * 
   * @return Orm\Metadata\EntityMetadata Entity metadata
   */
  public function getMetadata()
  {
    return $this->_metadata;
  }

  /**
   * createEntity 
   *
   * Return a new entity instance
   * 
   * @return IEntity $entity The new entity instance
   */
  public function createEntity()
  {
    $className = $this->getMetadata()->getClassName();

    return new $className();
  }

  /**
   * findOne
   *
   * Find one entity by provided criteria
   * 
   * @return IEntity|null
   */
  public function loadOne(array $criteria = array(), array $associations = array())
  {
    $sql = $this->getSelectSql($criteria, $associations);
    $data = $this->completeCriteria($criteria);
    $result = $this->execute($sql, $data['params'], $data['types']);

    if (! empty($result)) {
      return $this->populateEntity($this->createEntity(), $result);
    }
    return null;
  }

  /**
   * loadMany
   *
   * Load many entity instances
   * 
   * @param array $criteria The entity select criteria
   * @param Entity\ICollection $collection Optional collection instance to populate
   * @return array
   * @todo  Make this method return a collection!
   */
  public function loadMany(array $criteria = array(), Entity\ICollection $collection = null)
  {
    $sql = $this->getSelectSql($criteria);
    $data = $this->completeCriteria($criteria);
    $result = $this->execute($sql, $data['params'], $data['types']);

    $results = array();
    if (! empty($result)) {
      foreach ($result as $row) {
        $results[] = $this->populateEntity($this->createEntity(), $row);
      }
    }
    return $results;
  }

  /**
   * completeCriteria
   *
   * Validates and completes the criteria arguments passed to the
   * findByX methods
   * 
   * @param  array $criteria Field name to field value array
   * @return array Turple array of sql param values and param data types
   */
  protected function completeCriteria(array $criteria)
  {
    $data = array(
      'params' => array(),
      'types' => array()
    );
    if (! empty($criteria)) {
      $mappings = $this->getMetadata()->getFieldMappings();
      foreach($criteria as $fieldName => $fieldValue) {
        if (isset($mappings[$fieldName])) {
          if (isset($mappings[$fieldName]['dataType'])) {
            $data['types'][] = $mappings[$fieldName]['dataType'];
          }
          $data['params'][] = $fieldValue;
        }
      }
    }
    return $data;
  }

  /**
   * execute
   *
   * Execute a prepared SQL statement and return the result
   * 
   * @param  string $sql The SQL statement to execute
   * @param  array  $params SQL binded parameter values
   * @param  array  $types Column data types for the binded parameters 
   * @return array Array of all results
   */
  protected function execute($sql, array $params = array(), array $types = array())
  {
    $stmt = $this->_dbApdater->query($sql, $params);

    return $stmt->fetchAll();
  }


  /**
   * getSelectSql
   *
   * Return the SELECT sql statement for this entity
   * 
   * @param  array  $criteria     The criteria parameters
   * @param  array  $associations The entity associations
   * @return  The SQL Select statement string
   */
  public function getSelectSql(array $criteria = array(), array $associations = array())
  {
    $joinSql = '';
    $orderSql = '';
    $conditionSql = '';

    if (! empty($associations)) {
      if (isset($associations['type']) && $associations['type'] === Metadata\EntityMetadata::ASSOC_MANY_TO_MANY) {
        $joinSql .= $this->getSelectManyToManyJoinSql($associations);
      }
      if (isset($associations['orderBy']) && ! empty($criteria)) {
        $orderSql .= $this->getCollectionOrderBySql($criteria, $associations);
      }
    }
    if (! empty($criteria)) {
      $conditionSql .= $this->getSelectConditionSql($criteria);
    }

    return 
      'SELECT ' 
      . $this->getSelectColumnListSql() 
      . ' FROM '
      . $this->_metadata->getTableName() . ' '
      . $this->getTableAlias($this->_metadata->getClassName())
      . $joinSql
      . $conditionSql
      . $orderSql;
  }

  /**
   * getSelectColumnListSql
   *
   * Return the SQL statement for a select column list
   *  
   * @return string The SQL column list
   */
  protected function getSelectColumnListSql()
  {
    if (! strlen($this->_selectColumnSql)) {
      
      $columns = array();
      $metadata = $this->_metadata;
      $fieldNames = $metadata->getFieldMapping();

      foreach ($fieldNames as $columnName => $fieldName) {
        $columns[] = $this->getSelectColumnSql($fieldName, $metadata);
      }
      $assocColumns = $this->getSelectJoinColumnsSql($metadata);
      if (strlen($assocColumns) && ! empty($columns)) {
        $assocColumns = ", " . $assocColumns;
      }
      $this->_selectColumnSql = implode(',', $columns) . $assocColumns;
    }
    return $this->_selectColumnSql;
  }

  /**
   * getSelectColumnSql
   *
   * Return the SQL for one select column
   * 
   * @param  [type]                  $fieldName [description]
   * @param  Metadata\EntityMetadata $metadata  [description]
   * @return [type]                             [description]
   */
  public function getSelectColumnSql($fieldName, Metadata\EntityMetadata $metadata)
  {
    $column = $metadata->getColumn($fieldName);
    $sql = $this->getTableAlias($metadata->getClassName()) . '.' . $column;
    $alias = $column . $this->_tableIndex++;

    if (! isset($this->_columnNames, $alias)) {
      $this->_columnNames[$alias] = $column;
    }
    return $sql . ' as ' . $alias; 
  }

  /**
   * getSelectJoinColumnSql
   *
   * Return the join column SQL statement
   * 
   * @param  Metadata\EntityMetadata $metadata [description]
   * @return string The JOIN column statement as a string
   */
  public function getSelectJoinColumnSql(Metadata\EntityMetadata $metadata)
  {
    $sql = '';
    $mappings = $metadata->getAssociationMappings();

    if (! empty($mappings)) {
      foreach($mappings as $fieldName => $mapping) {
        if ($mapping['isOwningSide'] && $mapping['type'] == Metadata\EntityMetadata::ASSOC_ONE_TO_ONE) {
          foreach($mapping['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
            $columnAlias = $sourceColumn . $this->_tableIndex++;
            if (strlen($sql)) $sql = ', ';
            $sql .= $this->getTableAlias($metadata->getClassName()) . '.' . $sourceColumn . ' as '. $columnAlias;
            if (! isset($this->_columnNames[$columnAlias])) $this->_columnNames[$columnAlias] = $sourceColumn;
          }
        }
      }
    }
    return $sql;
  }

  /**
   * getSelectConditionSql
   *
   * Return the conditional SQL statement for a select query
   * 
   * @return string The conditional statement
   */
  protected function getSelectConditionSql(array $criteria = array())
  {
    $sql = '';
    if (! empty($criteria)) {

      $metadata = $this->_metadata;
      $fields = $metadata->getFields();

      foreach ($criteria as $fieldName => $fieldValue) {
        
        if (strlen($sql)) $sql .= ' AND ';

        if (isset($fields[$fieldName])) {
          $sql .= $this->getTableAlias($metadata->getClassName()) . '.' . $metadata->getColumn($fieldName);
        } 
        else if ($metadata->hasAssociation($fieldName)) {
          $assoc = $metadata->getAssociationMapping($fieldName);
          
          if ($assoc['isOwningSide']) {
            throw new \InvalidArgumentExecption(
              'Invalid inverse association for ' . $fieldName . ' for entity '. $metadata->getClassName()
            );
          }
          $sql .= $this->getTableAlias($metadata->getClassName()) . '.' . $assoc['joinColumns'][0]['name'];
        } else {
          $sql .= $fieldName;
        }
        $sql .= (is_null($fieldName)) ? ' IS NULL' : ' = ?';
      }
    }
    return $sql;
  }

  /**
   * getCollectionOrderBySql
   *
   * Return a order collection SQL cause statement
   * 
   * @param  array  $order [description]
   * @param  [type] $alias [description]
   * @return [type]        [description]
   */
  public function getCollectionOrderBySql(array $order, $alias)
  {
    foreach($order as $fieldName => $orderBy) {
      if (! $this->_metadata->hasField($fieldName)) {
        throw new \InvalidArgumentException('The field name %s could not be found for entity %s'); 
      }
      $sql = (strlen($sql)) ? ',' : ' ORDER BY ';
      $column = $this->_metadata->getColumn($fieldName);
      $sql .= $alias . '.' . $column .' '. $orderBy;
    }
    return $sql;
  }

  /**
   * getTableAlias
   *
   * Return the database table alias, if the class name
   * has not yet been used within the query another alias will be
   * generated
   * 
   * @param  string $className The class name
   * @return string The table alias
   */
  protected function getTableAlias($className)
  {
    if (isset($this->_tableAliases[$className])) {
      return $this->_tableAliases[$className];
    } else {
      $alias = 'tbl' . $this->_tableIndex++;
      $this->_tableAliases[$className] = $alias;

      return $alias;
    }
  }

  /**
   * loadManyToManyCollection
   *
   *  Load a many to many collection 
   * 
   * @return [type] [description]
   */
  protected function loadManyToManyCollection(array $association, Entity\IEntity $sourceEntity, Entity\ICollection $collection)
  {
    $criteria = array();
    $metadata = array();
    $em = $this->_entityManager;

    $metadata['source'] = $em->getEntityMetadata($association['sourceEntityName']);

    if ($association['isOwningSide']) {

      for($x = 0; $x < count($association['sourceEntityName']); $x++) {
      
        $joinColumn = $association['joinTable']['joinColumns'][$x];;
        $joinKeys = array(
          'relationKeyColumn' => $joinColumn['name'],
          'sourceKeyColumn' => $joinColumn['referencedColumn']
        );
      
        if ($metadata['source']->hasForeignIdentity()) {
          $field = $metadata['source']->getFieldForColumn($joinKeys['sourceKeyColumn']);
          $value = $metadata['source']->getRelflectionField($field)->getValue($sourceEntity);

          if ($metadata['source']->hasRelationship($field)) {
            if (! $value instanceof Entity\IEntity) {
              throw new \Exception('The foreign identity must be of type IEntity');
            }
            $metadata['target'] = $em->getEntityMetadata($value->getEntityName());
            $foreignKey = $metadata['target']->getSingleIdentityField();
            $value = intval($value->getValue($foreignKey));
          }
          $columnName = $association['joinTable']['name'] . '.' . $joinColumn['name'];
          $criteria[$columnName] = $value;
        
        } else if ($metadata['source']->hasField($joinKey['sourceKeyColumn'])) {

          $sourceFieldName = $metadata['source']->getField($joinKeys['sourceKeyColumn']);
          $columnName = $association['joinTable']['name'] . '.' . $joinColumn['name'];
          $criteria[$columnName] = $metadata['source']->getReflectionField($sourceFieldName)->getValue($sourceEntity);
        
        } else {
          throw new \Exception(sprintf('The source key column %s must map to a defined field', $joinKey['sourceKeyColumn']));
        }
      }
    } else {
      /** Inverse side of Many-To-Many association **/
      $owner = $em
        ->getEntityMetadata($association['targetEntityName'])
        ->getAssociationMapping($association['mappedByColumn']); 

      for ($x = 0; $x < count($owner['joinTable']['inverseJoinColumns']); $x++) {

        $joinColumn = $owner['joinTable']['joinColumns'][$x];
        $joinKeys = array(
          'relationKeyColumn' => $joinColumn['name'],
          'sourceKeyColumn' => $joinColumn['referencedColumn']
        );

        if ($metadata['source']->hasForeignIdentity()) {
          $field = $metadata['source']->getFieldForColumn($joinKeys['sourceKeyColumn']);
          $value = $metadata['source']->getReflectionField($field)->getValue($sourceEntity);
        
          if ($metadata['source']->hasAssociation($field)) {
            if (! $value instanceof IEntity) {
              throw new \InvalidArgumentException('The foreign identity value must be instance of type IEntity');
            }
            $metadata['target'] = $em->getEntityMetadata($owner['targetEntityName']);
            $fieldName = $metadata['target']->getSingleIdentityField();
            $value = $metadata['target']->getReflectionField($fieldName)->getValue($value);
          }
          $columnName = $association['joinTable']['name'] . '.' . $joinColumn['name'];
          $criteria[$columnName] = $value;

        } else if ($metadata['source']->hasField($joinKeys['sourceKeyColumn'])) {
            $sourceFieldName = $metadata['source']->getField($joinKeys['sourceKeyColumn']);
            $columnName = $association['joinTable']['name'] . '.' . $joinColumn['name'];
            $criteria[$columnName] = $metadata['source']->getReflectionField($sourceFieldName)->getValue($sourceEntity);
        } else {
          throw \Exception('The source key column %s must map to a defined field for entity %s');
        }
      }
    }

    $sql = $this->getSelectSql($criteria, $association);
    $data = $this->completeCriteria($criteria);
    $results = $this->execute($sql, $data['params']);

    if (! empty($results)) {
      $collection->setLoaded(true);
      for ($x = 0; $x < count($results); $x++) {
        $target = $this->createEntity()
      }
    }
    return $collection;


      var sql = getSelectSql(criteria, mapping);
      var data = completeParameters(criteria);
      var results = executeQuery(sql, data[1], data[2]);

      if (isQuery(results) && results.recordCount) {
        /** Set loaded state, avoids call to set() causing an infinate load() loop **/
        collection.setLoaded(true);
        for (var x = 1; x <= results.recordCount; x++) {
          var target = createEntity(results, x);
          arguments.collection.set(x, target);
        }
      }

    </cfscript>
  </cffunction>








}