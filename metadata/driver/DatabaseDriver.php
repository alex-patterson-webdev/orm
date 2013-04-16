<?php

namespace Orm\Metadata\Driver;

use Orm\Database;

/**
 * DatabaseDriver
 *
 * Database Metadata driver
 * 
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 */
 class DatabaseDriver implements IDriver
{
  /**
   * $_options
   *
   * @var array Driver configuration options
   */
  protected $_options = array();

  /**
   * $_entityTableName
   *
   * @var string Database table name that holds the
   * entity metadata information
   */
  protected $_entityTableName = '';

  /**
   * $_fieldTableName
   * 
   * @var string Database table name that holds the
   * entity field metadata
   */
  protected $_fieldTableName = '';

  /**
   * $_associationTableName
   *
   * @var string Databse table name that holds the
   * entity association metadata mappings
   */
  protected $_associationTableName = '';


  protected $_entityMetadata = array();

  protected $_fieldMetadata = array();

  protected $_associationMetadata = array();

  /**
   * $_dbAdapter
   * 
   * @var Orm\Database\Adapter
   */
  protected $_dbAdapter = null;
  
  /**
   * $_names
   *
   * @var array All entity names that the driver holds metadata for
   */
  protected $_names = array();

  /**
   * _construct
   * 
   * @param Orm\Database\Adapter $dbAdapter The database adapter
   */
  public function __construct(Database\Adapter $dbAdapter, array $options)
  {
    $this->_dbAdapter = $dbAdapter;
    $this->setOptions($options);

    return $this;
  }

  /**
   * setOptions
   *
   * Set the options for the driver
   * 
   * @param array $options The driver options
   */
  protected function setOptions(array $options)
  {
    foreach($options as $option => $value) {
      switch(strtolower($option)) {
        case 'entitytablename':
          $this->setEntityTableName($value);
        break;
        case 'fieldtablename':
          $this->setFieldTableName($value);
        break;
        case 'associationtablename':
          $this->setAssociationTableName($value);
        break;
        case 'dbadapter':
          $this->setDatabaseAdapter($value);
        break;
        default:
          $this->_options[$option] = $value;
      }
    }
  }

  /**
   * getDbAdapter
   *
   * Return the database adapter instance
   * 
   * @return Orm\Database\Adpater The database adapter instance
   */
  protected function getDatabaseAdapter()
  {
    return $this->_dbAdapter;
  }

  /**
   * setDatabaseAdapter
   *
   *  Set the database adapter instance
   * 
   * @param Orm\Database\Adapter $dbAdapter The database adapter
   */
  public function setDatabaseAdapter(Orm\Database\Adapter $dbAdapter)
  {
    $this->_dbAdapter = $dbAdapter;
  }

  /**
   * getEntityTableName
   *
   * Return the table name that holds the entity 
   * metadata definitions
   * 
   * @return string The database table name representing the
   * entity metadata
   */
  public function getEntityTableName()
  {
    return $this->_entityTableName;
  }

  /**
   * setEntityTableName
   *
   * Set the database table name that represents the entity
   * metadata
   * 
   * @param string $tableName The table name
   */
  public function setEntityTableName($tableName)
  {
    $this->_entityTableName = $tableName;
  }

  /**
   * getFieldTableName
   *
   * Return the field table name
   * 
   * @return string The database table name for field
   * metadata
   */
  public function getFieldTableName()
  {
    return $this->_fieldTableName;
  }

  /**
   * setFieldTableName
   *
   * set the table name that holds the metadata
   * for entity fields
   * 
   * @param string $tableName The table name
   */
  public function setFieldTableName($tableName)
  {
    $this->_fieldTableName = $tableName;
  }

  /**
   * getAssociationTableName
   *
   * Return the database table name that holds the
   * entity association table mapping
   * 
   * @return string The table name
   */
  public function getAssociationTableName()
  {
    return $this->_associationTableName;
  }

  /**
   * setAssociationTableName
   *
   * Set the association table name
   * 
   * @param string $tableName The table name string
   */
  public function setAssociationTableName($tableName)
  {
    $this->_associationTableName = $tableName;
  }

  /**
   * executeQuery
   *
   *  Execute the provided SQL statement and return the
   *  result set
   * 
   * @param string $sql The SQL statement to execute
   * @return array  $result
   */
  protected function createQuery($tableName)
  {
    return $this->getDatabaseAdapter()->select()->table($tableName);
  }

  /**
   * loadEntityMetadata
   * 
   * @param string $tableName
   * @return  array $metadata 
   */
  protected function loadMetadata($query)
  {
    $db = $this->getDatabaseAdapter();
    return $db->query($query)->fetchAll();
  }

  /**
   * getAllEntityNames
   * 
   * @return [type] [description]
   */
  public function loadEntityNames()
  {
    if (empty($this->_entityNames)) {
      $db = $this->getDatabaseAdapter();
      $q = $db->select()->from($this->getEntityTableName(), array('name'))->orderBy(array('name asc'));
      $this->_entityNames = $db->fetchCol($q);
    }
    return $this->_entityNames;
  }

  /**
   * loadEntityMetadata
   * 
   * @param  [type] $tableName [description]
   * @param  array  $orderBy   [description]
   * @return [type]            [description]
   */
  protected function loadEntityMetadata($entityName)
  {
    if (! isset($this->_entityMetadata[$entityName])) {
      $tableName = $this->getFieldTableName();
      $query = $this->createQuery($tableName)->where('name = ?', $entityName);
      $result = $this->loadMetadata($query);
      $this->_entityMetadata[$entityName] = $result[0];
    }
    return $this->_entityMetadata[$entityName];
  }

  /**
   * loadFieldMetadata
   * 
   * @param  [type] $tableName [description]
   * @return [type]            [description]
   */
  protected function loadFieldMetadata($entityName)
  {
    if (! isset($this->_fieldMetadata[$entityName])) {
      $tableName = $this->getFieldTableName();
      $query = $this->createQuery($tableName)->where('entity_name = ?', $entityName);
      $this->_fieldMetadata[$entityName] = $this->loadMetadata($query);
    }
    return $this->_fieldMetadata[$entityName];
  }

  /**
   * loadAssociationMetadata
   *
   * Load association metadata
   * 
   * @param  [type] $tableName [description]
   * @return [type]            [description]
   */
  protected function loadAssociationMetadata($entityName)
  {
    if (! isset($this->_associationMetadata[$entityName])) {
      $tableName = $this->getAssociationTableName();
      $query = $this->createQuery($tableName)->where('entity_name = ?', $entityName);
      $this->_associationMetadata[$entityName] = $this->loadMetadata($query);
    }
    return $this->_associationMetadata[$entityName];
  }

  /**
   * getAllEntityNames
   *
   * Return all the entity names that the driver holds metadata for
   * 
   * @return [type] [description]
   */
  public function getAllEntityNames()
  {
    if (empty($this->_names)) {
      $this->_names = $this->loadEntityNames();
    }
    return $this->_names;
  }

  /**
   * getEntityMetadata
   *
   * Return the entity metadata for a given entity name
   * 
   * @param  [type] $entityName [description]
   * @return [type]             [description]
   */
  public function getEntityMetadata($entityName)
  {
    if (! isset($this->_entityMetadata[$entityName])) {
      $this->_entityMetadata[$entityName] = $this->loadEntityMetadata($entityName);
    }
    return $this->_entityMetadata[$entityName];
  }

  /**
   * getFieldMetadata
   *
   *  Return the field metadata for a given entity name
   * 
   * @param  [type] $entityName [description]
   * @return [type]             [description]
   */
  public function getFieldMetadata($entityName)
  {
    if(! isset($this->_fieldMetadata[$entityName])) {
      $this->_fieldMetadata[$entityName] = $this->loadFieldMetadata($entityName);
    }
    return $this->_fieldMetadata[$entityName];
  }

  /**
   * getAssociationMetadata
   *
   * Return the association metadata for a given entity name
   * 
   * @param  [type] $entityName [description]
   * @return [type]             [description]
   */
  public function getAssociationMetadata($entityName)
  {
    if (! isset($this->_associationMetadata[$entityName])) {
      $this->_associationMetadata[$entityName] = $this->loadAssociationMetadata($entityName);
    }
    return $this->_associationMetadata[$entityName];
  }

  /**
   * populate
   *
   * Populate the entity metadata
   * 
   * @param  Metadata\Factory $factory    [description]
   * @param  [type]           $entityName [description]
   * @return [type]                       [description]
   */
  public function populate(Metadata\Factory $factory, $entityName)
  {
    $entityData = $this->getEntityMetadata($entityName);
    $className = $entityData['class_name'];
    $metadata = $factory->newMetadata($className);

    if (! $metadata instanceof Metadata\EntityMetadata) {
      throw new \Exception('Could not create metadata for entity ' . $entityName);
    }
    
    /** Set the basic entity metadata **/
    $metadata->setEntityName($entityName);
    $metadata->setClassName($className);
    $metadata->setTableName($entityData['table_name']);
    $metadata->setRepositoryClassName($entityData['repository_class_name']);

    /** Field mappings **/
    $fieldMappings = $this->getFieldMetadata($entityName);
    foreach($fieldMappings as $mapping) {
      $fieldMapping = array(
        'fieldName' => $mapping['field_name'],
        'columnName' => $mapping['column_name'],
        'identity' => $mapping['is_identity'],
        'dataType' => $mapping['data_type'],
        'dataLength' => $mapping['data_length'],
        'defaultValue' => $mapping['default_value']
      );
      $metadata->addFieldMapping($fieldMapping);
    }

    /** Association mappings **/
    $assocMappings = $this->getAssociationMetadata($entityName);
    foreach($assocMappings as $mapping) {
      $assocMapping = array(
        'type' => $mapping['association_type'],
        'fieldName' => $mapping['field_name'],
        'identity' => $mapping['identity'],
        'sourceEntityName' => $mapping['entity_name'],
        'targetEntityName' => $mapping['target_entity_name'],
        'mappedByColumn' => $mapping['mapped_by_column_name'],
        'inversedByColumn' => $mapping['inversed_by_column_name'],
        'loadType' => $mapping['load_type'],
        'joinColumns' => explode(',', $mapping['join_column_names']),
        'referencedColumns' => explode(',', $mapping['referenced_column_names']),
        'joinTable' => array(
          'name' => $mapping['join_table_name'],
          'joinColumns' => array(),
          'inverseJoinColumns' => array()
        )
      );
      $metadata->addAssociationMapping($assocMapping);
    }
    return $metadata;
  }


}