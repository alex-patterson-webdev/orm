<?php

/**
 * Strategies_Entity_Mapper_Abstract
 *
 * Base class for database abstraction layer, implements the 
 * single table mapper pattern. Each child mapper acts as the intermediary
 * layer, for persistance and querying of the mapped database table and 
 * in memory objects Entity instance.  
 *
 * @author Alex Patterson
 */
abstract class Strategies_Entity_Mapper_Abstract
{
  /**
   * $_dbAdapter
   *
   *  Database adapter, used for the creation of select instances
   * 
   * @var Zend_Db_Adapter_Abstract
   */
  protected $_dbAdapter = null;

  /**
   * $_entityName
   * 
   * @var string The entity name
   */
  protected $_entityName = "";

  /**
   * $_className
   * 
   * @var string The fully qualified class name for this entity
   */
  protected $_className = "";

  /**
   * $_tableName
   *
   * @var string The mapped database table name that this entity
   */
  protected $_tableName = "";

  /**
   * $_tableGateway
   *
   * @var Zend_Db_Table The database table gateway instance
   */
  protected $_tableGateway = null;

  /**
   * $_identifiers
   *
   * @var array Array of column names to identity field names
   * (This can be composite!)
   */
  protected $_identifiers = array();

  /**
   * $_fields
   * 
   * @var array A map of database column names to field names
   */
  protected $_fields = array();

  /**
   * $_columns
   *
   * @var array A map of the entity field names to column names
   */
  protected $_columns = array();

  /**
   * $_relationships
   *
   * Map of the entity relationships
   * 
   * @var array
   */
  protected $_relationships = array();

  /**
   * $_identityMap
   * 
   * @var array Map of loaded entities
   */
  protected $_identityMap = array();

  /**
   * __construct
   *
   *  
   * @param Zend_Db_Adapter_Abstract $dbAdapter [description]
   */
  public function __construct()
  {
    $this->init();

    return $this;
  }

  /**
   * getDbAdapter
   *
   * Return the database adapter
   * 
   * @return Zend_Db_Adapter_Abstract The database adapter
   */
  public function getDbAdapter()
  {
    return $this->getTableGateway()->getAdapter();
  }

  /**
   * init
   *
   * Initilize the metadata mapper
   * 
   * @return void
   */
  protected function init()
  {
    /** Get Zend to discribe the table **/
    $this->_metadata = $this->getTableGateway()->info();

    /** Identity fields & primary keys **/
    foreach ($this->_metadata['primary'] as $columnName) {
      $fieldName = $this->formatColumnNameAsPropertyName($columnName);
      $this->_identifiers[] = $fieldName; 
    }

    /** Field & column names**/
    foreach ($this->_metadata['cols'] as $columnName) {
      $fieldName = $this->formatColumnNameAsPropertyName($columnName);
      $this->_fields[$columnName] = $fieldName; 
      $this->_columns[$fieldName] = $columnName;
    }

  }

  /**
   * getEntityName
   *
   * Return the entity name
   * 
   * @return string The entity name
   */
  public function getEntityName()
  {
    return $this->_entityName;
  }

  /**
   * setEntityName
   *
   * Set the name of the entity that this mapper is responsible for
   * mapping data to
   * 
   * @param string $entityName The entity name
   */
  protected function setEntityName($entityName)
  {
    $this->_entityName = $entityName;
  }

  /**
   * getClassName
   *
   * Return the fully qualified class name of the
   * mapped entity
   * 
   * @return string The entity class name
   */
  public function getClassName()
  {
    return $this->_className;
  }

  /**
   * setClassName
   *
   * Set the mapped class name (FQCN)
   * 
   * @param string $className The name of the class that this mapper
   * is responsible for mapping data to
   */
  protected function setClassName($className)
  {
    $thios->_className = $className;
  }

  /**
   * getTableName
   *
   * Return the mapped table name string
   * 
   * @return string The mapped database table name
   */
  public function getTableName()
  {
    return $this->_tableName;
  }

  /**
   * setTableName
   *
   * Set the mapped database table name
   * 
   * @param string $tableName The mapped database table name
   */
  protected function setTableName($tableName)
  {
    $this->_tableName = $tableName;
  }

  /**
   * getIdentifiers
   *
   * An array of identifier fields
   * 
   * @return array The identity field names
   */
  public function getIdentifiers()
  {
    return $this->_identifiers;
  }

  /**
   * setIdentifiers
   *
   * Set the identitifer field names
   * 
   * @param array $identifiers The indentity fields
   */
  public function setIdentifiers(array $identifiers)
  {
    $this->_identifiers = $identifiers;
  }

  /**
   * hasCompositeKey
   *
   * Check if this entity has a composite identifier
   * 
   * @return boolean
   */
  public function hasCompositeIdentifier()
  {
    return (count($this->_identifiers) > 1) ? true : false;
  }

  /**
   * getIdentityAsString
   *
   * Return a imploded string representation of the entity key
   * (converts the identity array into a string)
   * 
   * @param  array $identity The identity array
   * @return string
   */
  protected function getIdentityAsString(array $identity, $delim = '-')
  {
    return implode($delim, $identity);
  }

  /**
   * isInIdentityMap
   *
   * Check if a proivided identity is within the identity map
   * 
   * @param  array $id The entity's identity
   * @return boolean
   */
  protected function isInIdentityMap(array $id)
  {
    $key = $this->getIdentityAsString($id);
    return (isset($this->_identityMap[$key])) ? true : false;
  }

  /**
   * getFromIdentityMap
   *
   * Return a loaded entity instance by priamry key id
   * 
   * @param  array $id The entity identity
   * @return mixed
   */
  protected function getFromIdentityMap(array $id)
  {
    $key = $this->getIdentityAsString($id);
    return (isset($this->_identityMap[$key])) ? $this->_identityMap[$key] : null;
  }

  /**
   * addToIdentityMap
   *
   * Add a instance to the identity map
   * 
   * @param Entity
   */
  protected function addToIdentityMap($entity)
  {
    $key = $this->getIdentityAsString($entity->getId());
    $this->_identityMap[$key] = $entity;
  }

  /**
   * removeFromIdentityMap
   *
   * Remove an existing entity from the identity map
   * 
   * @param  array $id The entity identity
   * @return boolean
   */
  protected function removeFromIdentityMap(array $id)
  {
    $key = $this->getIdentityAsString($id);
    if (isset($this->_identityMap[$key])) {
      unset($this->_identityMap[$key]);
      return true;
    }
    return false;
  }

  /**
   * getTableAdapter
   *
   * Return the table gateway instance for this mapped table
   * 
   * @return Zend_Db_Table $table The database table gateway
   */
  protected function getTableGateway()
  {
    if (is_null($_tableGateway)) {
      $this->_tableGateway = new Zend_Db_Table($this->getTableName());
    }
    return $this->_tableGateway;
  }

  /**
   * setTableGateway
   *
   * Set the database table gateway instance
   * 
   * @param Zend_Db_Table $tableGateway The database gateway
   */
  protected function setTableGateway(Zend_Db_Table $tableGateway)
  {
    $this->_tableGateway = $tableGateway;
  }

  /**
   * getColumnNameAsPropertyName
   *
   * Format a database column name as a property name
   * (This turns 'column_name' into 'propertyName')
   * 
   * @param  string $columnName The table column name
   * @return string             The column formatted as a property name
   */
  protected function formatColumnNameAsPropertyName($columnName)
  {
    $name = explode("_", $columnName);
    for ($x = 0; $x < count($name); $x++) {
      $name[$x] = ($x == 0) ? strtolower($name[$x]) : ucfirst($name[$x]);
    }
    return implode("", $columnName);
  }

  /**
   * getTableMetadata
   *
   * Return the metadata information for this table
   * 
   * @return array The table metadata
   */
  protected function getTableMetadata()
  {
    return $this->_metadata;
  }

  /**
   * getFieldNameForColumn
   *
   * Return the property name for a given column name
   * 
   * @param string $columnName The column name
   * @return string The property name
   * @throws InvalidArgumentException
   */
  public function getFieldNameForColumn($columnName)
  {
    if (isset($this->_fields[$columnName])) {
      return $fields[$columnName];
    }
    throw \InvalidArgumentException("The column '$columnName' could not be found");
  }

  /**
   * getColumnNameForField
   *
   * Return the name of the provided column
   * 
   * @param string $fieldName The entity field name
   * @return string  The database column name
   */
  public function getColumnNameForField($fieldName)
  {
    if (isset($this->_columns[$fieldName])) {
      return $this->_columns[$fieldName];
    }
    throw \InvalidArgumentException("The field '$fieldName' could not be found");
  }

  /**
   * createEntity
   *
   * Create a new entity instance
   * 
   * @return [type] [description]
   */
  protected function createEntity()
  {
    $className = $this->getClassName();
    return new $className($this);
  }

  /**
   * populateEntity
   *
   * Populate an entity instance with provided data
   *
   * @param $entity The entity instance to populate
   */
  protected function populateEntity(Strategies_Entity_Abstract $entity, array $data)
  {

  }

  /**
   * createQuery
   *
   * Begin a new query instance. This is automatically
   * constructed to map to this table
   * 
   * @return Zend_Db_Table_Select $query
   */
  protected function createQuery()
  {
    return $this->getTableGateway()->select();
  }

  /**
   * createFindByIdQuery
   *
   * Return a find by identity query for this table
   * 
   * @return Zend_Db_Table_Select The find by id query
   */
  protected function createFindByIdQuery(array $id)
  {
    $keys = array();
    foreach($this->_identifiers as $key => $field) {
      $keys[$this->_columns[$field]] = 
    }

  }


  /**
   * findById
   *
   * Find a entity by its unique identity
   * 
   * @param mixed (array|integer) $id
   * @return Strategies_Entity_Abstract|null A single entity instance or null
   * if the entity cannot be found
   */
  protected function findById($id)
  {
    if (! is_array($id)) {
      if (is_scalar($id)) {
        $id = explode(',', $id);
      } else {
        throw new \InvalidArgumentException("Invaid argument type for findById");
      }
    }
    if (! count($id) == count($this->_identifiers)) {
      throw (sprintf("Invaid number of identifiers for entity %s", $this->_entityName));
    } else {
      if ($this->isInIdentityMap($id)) {
        return $this->getFromIdentityMap($id);
      }
      $query = $this->createFindByIdQuery($id);
      $data   = $this->fetchOne($query);




$row = $table->fetchRow(
    $table->select()
        ->where('bug_status = :status')
        ->bind(array(':status'=>'NEW')
        ->order('bug_id ASC')
    );
    $table->getAdapter()->quoteInto('bug_id = ?', 1235)



      $query = $this->createQuery();

      $entity = $this->createEntity();
    }





    }



  public function find($id);

  public function findMany($where);

  public function save($entity);

  public function delete($id);

}