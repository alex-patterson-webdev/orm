<?php

namespace Orm\Metadata;

use Orm\Entity;

/**
 * EntityMetadata
 *
 * Entity metadata is an abstract base class for representing 
 * entity metadata. It defines the fields and entity realationships
 * so they can be mapped back to the correct fields and persist the
 * correct associations.
 * 
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 */
class EntityMetadata
{
  const ASSOC_ONE_TO_ONE = 'onetoone';
  const ASSOC_ONE_TO_MANY = 'onetomany';
  const ASSOC_MANY_TO_MANY = 'manytomany';

  const DATA_TYPE_INT = 'integer';
  const DATA_TYPE_VAR = 'varchar';
  const DATA_TYPE_TIMESTAMP = 'timestamp';

  const LOAD_EAGER = 'eager';
  const LOAD_LAZY = 'lazy';

  /**
   * $_entityName
   *
   * @var string The entity name
   */
  protected $_entityName = '';

  /**
   * $_className
   *
   * @var string The entity fully qualified class name
   */
  protected $_className = '';

  /**
   * $_tableName
   * 
   * @var string Database table name
   */
  protected $_tableName = '';

  /**
   * $_identityFields
   * 
   * @var array Map of column names to field names
   */
  protected $_identityFields = array();

  /**
   * $_fields
   *
   * @var array The entity field names
   */
  protected $_fields = array();

  /**
   * $_columns
   * 
   * @var array The table column names
   */
  protected $_columns = array();

  /**
   * $_fieldMappings
   * 
   * @var array An array map of column names to field names
   */
  protected $_fieldMappings = array();

  /**
   * $_reflectionClass
   * 
   * @var \ReflectionClass A reflection instance of the 
   * mapped entity
   */
  protected $_reflectionClass = null;

  /**
   * $_reflectionFields
   *
   * Array of loaded reflection fields
   * 
   * @var array
   */
  protected $_refelectionFields = array();

  /**
   * $_associationMappings
   * 
   * @var array Map of field names that represent a foreign association to
   * this entity
   */
  protected $_associationMappings = array();

  /**
   * $_foreignIdentity
   *
   * Flag that determines if the mapped entity has a relationship
   * which also represents the identity
   * 
   * @var boolean
   */
  protected $_foreignIdentity = false;

  /**
   * __construct
   *
   * @return EntityMetadata
   */
  public function __construct($className, array $metadata = array())
  {
    $this->_className = $className;
    $this->_reflectionClass = new \ReflectionClass($this->_className);

    if (! empty($metadata)) {
      $this->setMetadata($metadata);
    }
    return $this;
  }

  /**
   * setMetadata
   *
   * Set the entity metadata
   * 
   * @param array $metadata The entity metadata
   */
  protected function setMetadata(array $metadata)
  {
    foreach ($metadata as $option => $value){
      switch(strtolower($option)) {
        case 'entityname':
          $this->setEntityName($value);
        break;
        //case 'classname':
        //  $this->setClassName($value);
        //break;
        case 'tablename':
          $this->setTableName($value);
        break;
        case 'fieldmappings':
          if (! is_array($value)) throw new \InvalidArgumentException('Entity metadata for option "' . $option .'" must be an array.');
          $this->setFieldMappings($value);
        break;
        case 'associationmappings':
          if (is_array($value)) throw new \InvalidArgumentException('Entity metadata for option "' . $option .'" must be an array.');
          $this->setAssociationMappings($value);
        break;
      }
    }
  }

  /**
   * getEntityName
   * 
   * @return Return the entiy
   */
  public function getEntityName()
  {
    return $this->_entityName;
  }

  /**
   * setEntityName
   *
   * Set the name of the entity that this metadata 
   * represents
   * 
   * @param string $entityName The entity name
   */
  public function setEntityName($entityName)
  {
    $this->_entityName = $entityName;
  }

  /**
   * getClassName
   *
   * return the fully qualified class name of the entity
   * 
   * @return string The class name
   */
  public function getClassName()
  {
    return $this->className;
  }

  /**
   * getTableName
   *
   * Return the database table name
   * 
   * @return string The database table name
   */
  public function getTableName()
  {
    return $this->_tableName;
  }

  /**
   * setTableName
   *
   * Set the database table name that this entity maps to
   * 
   * @param string $tableName The database table name
   */
  public function setTableName($tableName)
  {
    $this->_tableName = $tableName;
  }

  /**
   * getIdentityFields
   *
   * Return the field names that represent the entities identity
   * 
   * @return array The field name array
   */
  public function getIdentityFields()
  {
    return $this->_identityFields;
  }

  /**
   * setIdentityFields
   *
   * Set the identity field names
   * 
   * @param  array $fields The identity field names
   */
  public function setIdentityFields(array $identityFields)
  {
    $this->_identityFields = $identityFields;
  }

  /**
   * getIdentityColumns
   *
   * Return the column names that represent the mapped tables
   * primary keys
   * 
   * @return array Primary key column name array
   */
  public function getIdentityColumns()
  {
    return $this->getColumnNames($this->getIdentityFields());
  }

  /**
   * hasCompositeKey
   *
   * Check if the mapped entity has more that one field
   * that represents its identity
   * 
   * @return boolean
   */
  public function hasCompositeIdentity()
  {
    return (count($this->_identityFields) > 1) ? true : false;
  }

  /**
   * getSingleIndentityField
   *
   * Return the single field name that represents the entity
   * identity
   * 
   * @return string The indetity field name
   * @throws  BadMethodCallException If the entity has a composite key
   */
  public function getSingleIdentityField()
  {
    if ($this->hasCompositeIdentity()) {
      throw new \BadMethodCallException(
        'Cannot get single identity field for entity "' . $this->_entityName . '". The entity has a composite identity'
      );
    }
    return $this->_identityFields[0];
  }

  /**
   * getSingleIdentityColumn
   *
   * Return the column name that represents the single primary key of the
   * mapped database table
   * 
   * @return string The identity column name
   */
  public function getSingleIdentityColumn()
  {
    return $this->getColumnName($this->getSingleIdentityField());
  }

  /**
   * hasForeignIdentity
   *
   * Flag that determines if one of the association fields also acts
   * as one of the identity fields of this entity.
   * 
   * @return boolean
   */
  public function hasForeignIdentity()
  {
    return $this->_foreignIdentity;
  }

  /**
   * getFieldMappings
   *
   * Return the entity field mappings
   * 
   * @return array Field mapping metadata 
   */
  public function getFieldMappings()
  {
    return $this->_fieldMappings;
  }

  /**
   * addFieldMapping
   *
   * Add a new field mapping to the entity metadata. Once added the
   * metadata will be validated and formatted to the correct
   * array strucure
   * 
   * @param string $fieldName The field name to hold the mapping
   * @param array  $mapping  The raw mapping metadata for this field
   */
  public function addFieldMapping(array $mapping)
  {
    $mappings = $this->completeFieldMapping($mapping);
    $fieldName = $mappings['fieldName'];

    if (isset($this->_fieldMappings[$fieldName]) || isset($this->_associationMappings[$fieldName])) {
      throw new InvalidArgumentException(
        "The field name '" . $fieldName ."'' has already been defined for entity '". $this->_entityName ."'"
      );
    }
    $this->_fieldMappings[$fieldName] = $mappings;
  }

  /**
   * completeFieldMapping
   *
   * Validate and complete the provided field metadata
   * 
   * @param  array $mapping The metadata to validate and complete
   * @return array The validated and completed field metadata
   */
  protected function completeFieldMapping(array $mapping)
  {
    if (! isset($mapping['fieldName'])) {
      throw new \InvalidArgumentException('Entity field metadata must has a field name defined');
    }
    if (! isset($mapping['dataType'])) {
      $mapping['dataType'] = 'varchar';
    }
    if (! isset($mapping['columnName']) || ! strlen($mapping['columnName'])) {
      $mapping['columnName'] = $mapping['fieldName'];
    }

    $this->_fields[$mapping['columnName']] = $mapping['fieldName'];
    $this->_columns[$fieldName] = $mapping['columnName'];

    if (isset($mapping['identity']) && true == $mapping['identity']) {
      if (! in_array($mapping['fieldName'], $this->_identityFields)) {
        $this->_identityFields[] = $mappings['fieldName'];
      }
    }

    return $mappings;
  }

  /**
   * getAssociationMappings
   *
   *  Return the assicuation mapping metadata for this entity
   * 
   * @return array Map of each fields relationship mappings
   */
  public function getAssociationMappings()
  {
    return $this->_associationMappings;
  }

  /**
   * hasAssociation
   *
   * Check if a field represents a entity association
   * 
   * @param string  $fieldName The name of the field
   * @return boolean
   */
  public function hasAssociation($fieldName)
  {
    return (isset($this->_associationMappings[$fieldName]) ? true : false);
  }

  /**
   * isSingleIdentityAssociation
   * 
   * @param  [type]  $fieldName [description]
   * @return boolean            [description]
   */
  public function isSingleIdentityAssociation($fieldName)
  {
    if (isset($this->_associationMappings[$fieldName])) {
      return ($this->_associationMappings[$fieldName]['type'] == self::ASSOC_ONE_TO_ONE) ? true : false;
    }
    return false;
  }

  /**
   * isCompositeIdentityAssociation
   *
   * Check if the provided field represents a multiple identity entity 
   * association
   * 
   * @param  [type]  $fieldName [description]
   * @return boolean            [description]
   */
  public function isCompositeIdentityAssociation($fieldName)
  {
    if (isset($this->_associationMappings[$fieldName])) {
      return ($this->_associationMappings[$fieldName]['type'] !== self::ASSOC_ONE_TO_ONE) ? true : false;
    }
    return false;
  }

 /**
   * getAssociationMapping
   *
   * Return the association mapping for a given field
   * 
   * @param string $fieldName The field name
   * @return array The association field mapping
   */
  public function getAssociationMapping($fieldName)
  {
    if (isset($this->_associationMappings[$fieldName])) {
      return $this->_associationMappings[$fieldName];
    } else {
      throw new \InvalidArgumentException(
        'Unknown association field "' . $fieldName . ' for entity "' . $this->_entityName. '".'
      );
    }
  }

  /**
   * addAssociationMapping
   *
   * Add a field mapping that represents a entity relationship
   * 
   * @param array $mapping Association mapping data
   */
  public function addAssociationMapping(array $mapping)
  {
    $mapping = $this->completeAssociationMapping($mapping);
    $fieldName = $mapping['fieldName'];
    if (isset($this->_fieldMappings[$fieldName]) || isset($this->_associationMappings[$fieldName])) {
      throw \InvalidArgumentException(
        sprintf('Duplicate field mapping for field %s in entity %s', $fieldName, $this->_entityName)
      );
    }
    $this->_associationMappings[$fieldName] = $mapping;
    $this->_reflectionFields[$fieldName] = $this->getReflectionClass()->getProperty($fieldName);
  }

  /**
   * completeAssociationMapping
   *
   * Validate and complete the mapping for entity associations. This ensures that
   * simple mapping can be completed with defaults and exceptions are thrown where
   * invalid association information is provided.
   * 
   * @param array $assocMapping The association mapping
   * @return array Validated mapping
   */
  protected function completeAssociationMapping(array $mapping)
  {
    $mapping['isOwningSide'] = true;

    if (! isset($mapping['fieldName'])) {
      throw new \nvalidArgumentException('Relationship mapping must have a field name');
    } else if (! isset($mapping['targetEntityName'])) {
      throw new \InvalidArgumentException("The 'targetEntityName' parameter is required for all association mappings");
    }

    if (! isset($mapping['sourceEntityName'])) $mapping['sourceEntityName']  = $this->_entityName;
    if (! isset($mapping['mappedByColumn'])) $mapping['mappedByColumn']  = '';
    if (! isset($mapping['inversedByColumn'])) $mapping['inversedByColumn'] = '';

    if (isset($mapping['identity']) && $mapping['identity'] == true) {
      if (! in_array($mapping['fieldName'], $this->_identityFields) && count($mapping['joinColumns']) > 1) {
        throw new \InvalidArgumentException('Cannot map composite identity as foreign identity');
      } else {
        $this->_identityFields[] = $mapping['fieldName'];
        $this->_foreignIdentity = true;
      }
    }

    if (count($mapping['joinColumns']) != count($mapping['referencedColumns'])) {
      throw new \InvalidArgumentException('Each joined column must has a referenced column');
    }

    $joins = array();
    for ($x = 0; $x < count($mapping['joinColumns']); $x++) {
      $joins[] = array(
        'columnName' => $mapping['joinColumns'][$x],
        'referencedColumn' => $mapping['referencedColumns'][$x]
      );
    }
    $mapping['joinColumns'] = $joins;

    if (! strlen($mapping['mappedByColumn'])) {

    } else {
      $mapping['isOwningSide'] = false;
    }
    if (isset($mapping['identity']) && $mapping['identity'] == true) {
      if (isset($mapping['type']) && $mapping['type'] == self::ASSOC_MANY_TO_MANY) {
        throw new InvalidArgumentException('Many to many associations fields cannot be defined as entity identitifers');
      }
    }
    if (! isset($mapping['loadScheme']) || $mapping['loadScheme'] !== self::LOAD_EAGER) {
      $mapping['loadScheme'] = self::LOAD_LAZY;
    }

    switch ($mapping['type']) {
      case self::ASSOC_ONE_TO_ONE:
        $mapping = $this->completeOneToOneMapping($mapping);
      break;
      case self::ASSOC_ONE_TO_MANY:
        $mapping = $this->completeOneToManyMapping($mapping);
      break;
      case self:ASSOC_MANY_TO_MANY:
        $mapping = $this->completeManyToManyMapping($mapping);
      break;  
      default:
        throw new \InvalidArgumentException(
          sprintf('Invalid association mapping type %s for field %s in entity %s', 
            $mapping['type'], $mapping['fieldName'], $this->_entityName
          )
        );
    }
    return $mapping;
  }

  /**
   * completeOneToOneMapping
   *
   * Validate and complete the one to one metadata mapping for this
   * field
   * 
   * @param array $mapping 
   * @return array $mapping The validated mapping for this association
   */
  protected function completeOneToOneMapping(array $mapping)
  {
    if (isset($mapping['joinColumns']) && ! empty($mapping['joinColumns'])) {
      $mapping['isOwningSide'] = true;
    }
    if ($mapping['isOwningSide']) {
      if (! isset($mapping['joinColumns']) || ! empty($mapping['joinColumns'])) {
        $mapping['joinColumns'] = array(
          'name' => $mapping['fieldName'] . '_id',
          'referencedColumn' => 'id'
        );
      }

      $mapping['columnsToFieldNames'] = array();
      $mapping['sourceToTargetKeyColumns'] = array();

      for ($x = 0; $x < count($mapping['joinColumns']); $x++) {
        $column = $mapping['joinColumns'][$x];

        if ($mapping['type'] == self::ASSOC_ONE_TO_ONE) {
          $column['unique'] = true;
        }
        if (! isset($column['name']) || ! strlen($column['name'])) {
          $column['name'] = $mapping['fieldName'] . '_id';
        }
        if (! isset($column['referencedColumn']) || ! len($column['referencedColumn'])) {
          $column['referencedColumn'] = 'id';
        }
        $mapping['sourceToTargetKeyColumns'][$column['name']] = $column['referencedColumn'];

        if (isset($column['fieldName'])) {
          $mapping['columnsToFieldNames'][$column['name']] = $column['fieldName'];
        } else {
          $mapping['columnsToFieldNames'][$column['name']] = $mapping['fieldName'];
        }

        $mapping['joinColumns'][$x] = $column;
      }
    }
    return $mapping;
  }

  /**
   * completeManyToManyMapping
   *
   * Validate and complete the many to many mapping
   * 
   * @param  array  $mapping [description]
   * @return [type]          [description]
   */
  protected function completeManyToManyMapping(array $mapping)
  {
    if ($mapping['isOwningSide']) {
      $sourceName = $mapping['sourceEntityName'];
      $targetName = $mapping['targetEntityName'];

      if (! isset($mapping['joinTable']['name'])) {
        $mapping['joinTable']['name'] = strtolower($sourceName . '_' . $targetName);
      }

      if (empty($mapping['joinTable']['joinColumns'])) {
        $mapping['joinTable']['joinColumns'][] = array(
          'name' => strtolower($sourceName . '_id'),
          'referencedColumn' => 'id',
          'onDelete' => 'cascade'
        );
      }
      if (empty($mapping['joinTable']['inverseJoinColumns'])) {
        $mapping['joinTable']['inverseJoinColumns'][] = array(
          'name' => strtolower($targetName . '_id'),
          'referencedColumn' => 'id',
          'onDelete' => 'cascade'
        );
      }

      $mapping['joinTableColumns'] = array();
      $mapping['relationToSourceKeyColumns'] = array();
      $mapping['relationToTargetKeyColumns'] = array();

      for ($x = 0; $x < count($mapping['joinTable']['joinColumns']); $x++) {
        $joinColumn = $mapping['joinTable']['joinColumns'][$x];
        if (! isset($joinColumn['name']) || ! strlen($joinColumn['name'])) {
          $joinColumn['name'] = strtolower($sourceName . '_id');
        }
        if (! isset($joinColumn['referencedColumn']) || ! strlen($joinColumn['referencedColumn'])) {
          $joinColumn['referencedColumn'] = 'id';
        }
        $mapping['relationToSourceKeyColumns'][$joinColumn['name']] = $joinColumn['referencedColumn'];
        $mapping['joinTableColumns'][] = $joinColumn['name'];
      }

      for ($y = 0; $y < count($mapping['joinTable']['inverseJoinColumns']); $y++) {
        $inverseColumn = $mapping['joinTable']['inverseJoinColumns'][$y];

        if (! isset($inverseColumn['name']) || ! strlen($inverseColumn['name'])) {
          $inverseColumn['name'] = strtolower($targetName . '_id');
        }

        if (! isset($inverseColumn['referencedColumn']) || ! strlen($inverseColumn['referencedColumn'])) {
          $inverseColumn['referencedColumn'] = 'id';
        }
        $mapping['relationToTargetKeyColumns'][$inverseColumn['name']] = $inverseColumn['referencedColumn'];
        $mapping['joinTableColumns'][] = $inverseColumn['name'];
      }
    }
    return $mapping;
  }

  /**
   * hasField
   *
   * Check if a field is defined for this entity
   * 
   * @param string    $fieldName The field name to validate
   * @return boolean
   */
  public function hasField($fieldName)
  {
    return (isset($this->_fieldMappings[$fieldName])) ? true : false;
  }

  /**
   * getFields
   *
   * Return the fields of the entity
   * 
   * @return array A field name array
   */
  public function getFields()
  {
    return $this->_fields;
  }

  /**
   * getFIeld
   *
   * Return the field name for a given column name
   * 
   * @param string $columnName The column name to check
   * @return string The field name when found, column name
   * when there is no matching field
   */
  public function getField($columnName)
  {
    if (isset($this->_fields[$columnName])) {
      return $this->_fields[$columnName];
    }
    return $columnName;
  }

  /**
   * getColumnName
   *
   * Return the column name for a given field name
   * 
   * @param string $fieldName
   * @return string The column name when found
   */
  public function getColumn($fieldName)
  {
    if (isset($this->_columns[$fieldName])) {
      return $this->_columns[$fieldName];
    }
    return $fieldName;
  }

  /**
   * getFieldDataType
   *
   *  Return the data type of the field
   * 
   * @param string $fieldName The field name
   * @return string The data type
   */
  public function getFieldDataType($fieldName)
  {
    if (isset($this->_fieldMappings[$fieldName])) {
      $mapping = $this->_fieldMappings[$fieldName];

      if (isset($mapping['type'])) return $mapping['type'];
    }
    return self::DATA_TYPE_VAR;
  }

  /**
   * getFieldForColumn
   *
   * Returns the name of a field for a given column, however it
   * will also check the field names defined for entity
   * association. This is mainly used when the entity has a identity 
   * field that is also a relationship with another entity 
   * 
   * @param string $columnName
   * @return string
   */
  public function getFieldForColumn($columnName)
  {
    if (isset($this->_fields[$columnName])) {
      return $this->_fields[$columnName];
    }
    $mappings = $this->getAssociationMappings();
    foreach ($mappings as $fieldName => $fieldMapping) {
      if (! empty($fieldMapping['joinColumns']) 
        && $columnName == $fieldMapping['joinColumns'][0]['columnName']) {
        return $fieldName;
      }
    }
  }

  /**
   * getReflectionClass
   *
   * Return the reflection instance of the mapped entity class
   * 
   * @return ReflectionClass
   */
  public function getReflectionClass()
  {
    return $this->_reflectionClass;
  }

  /**
   * getReflectionFields
   *
   * Return the entities loaded reflection fields 
   * 
   * @return array Map of reflection field instances
   */
  public function getReflectionFields()
  {
    return $this->_reflectionFields;
  }


  /**
   * getReflectionField
   *
   * Return a reflection property instance of the given
   * entity field name
   * 
   * @param  string $fieldName The field name
   * @return \ReflectionProperty
   */
  public function getReflectionField($fieldName)
  {
    if (isset($this->_reflectionFields[$fieldName])) {
      return $this->_reflectionFields[$fieldName];
    }
    throw new \InvalidArgumentException(
      sprintf('Unknown reflection field name %s for entity %s', $fieldName, $this->_entityName)
    );
  }

  /**
   * createEntityInstance
   *
   * Create a new instance of the entity
   * 
   * @return Orm\Entity\IEntity $entity 
   */
  public function createEntityInstance()
  {
    return $this->getReflectionClass()->newInstance();
  }

  /**
   * setFieldValue
   *
   * Set the value of the provided entity
   * 
   * @param [type] $entity     [description]
   * @param [type] $fieldName  [description]
   * @param [type] $fieldValue [description]
   */
  public function setFieldValue(Entity\IEntity $entity, $fieldName, $fieldValue)
  {
    $this->getReflectionField($fieldName)->setValue($entity, $fieldValue);
  }



} 