<?php

namespace Orm\Metadata\Driver;

use Orm\Database;
use Orm\Metadata;

/**
 * DatabaseDriver
 *
 * Database Metadata driver
 * 
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 */
 class DatabaseDriver extends Driver
{
  /**
   * $_dbAdapter
   * 
   * @var Orm\Database\Adapter
   */
  protected $_dbAdapter = null;
  
  /**
   * $_tableNames
   *
   * Array of database table names to fetch the metadata
   * from
   * 
   * @var array
   */
  protected $_tableNames = array();

  /**
   * _construct
   * 
   * @param Orm\Database\Adapter $dbAdapter The database adapter
   */
  public function __construct(\Zend_Db_Adapter_Abstract $dbAdapter, array $tableNames)
  {
    $this->_dbAdapter = $dbAdapter;
    $this->setTableNames($tableNames);

    return $this;
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
    // if (is_null($this->_dbAdapter)) {
    //   throw \Exception('The database adapter has not been defined');
    // }
    return $this->_dbAdapter;
  }

  /**
   * setDatabaseAdapter
   *
   *  Set the database adapter instance
   * 
   * @param Orm\Database\Adapter $dbAdapter The database adapter
   */
  public function setDatabaseAdapter(\Zend_Db_Adapter_Abstract $dbAdapter)
  {
    $this->_dbAdapter = $dbAdapter;
  }

  /**
   * setTableNames
   *
   * Set the database table names where the driver will fetch the 
   * metadata from
   * 
   * @param array $tableNames The names of the tables to set
   */
  public function setTableNames(array $tableNames)
  {
    foreach($tableNames as $type => $tableName) {
      switch($type) {
        case Driver::META_ENTITY:
          $this->setEntityTableName($tableName);
        break;
        case Driver::META_FIELDS:
          $this->setFieldTableName($tableName);
        break; 
        case Driver::META_ASSOC:
          $this->setAssociationTableName($tableName);
        break;
        default:
          throw new \InvalidArgumentException(sprintf('Unknown metadata type %s', $type));
      }
    }
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
   * loadAllEntityNames
   * 
   * @return array The entity names as a simple array
   */
  public function loadAllEntityNames()
  {
    if (! strlen($this->_entityTableName)) {
      throw \InvalidArgumentException('Missing entity table name');
    } else {    
      $db = $this->getDatabaseAdapter();
      $query = $db->select()->from($this->_entityTableName, array('entity_name'));
      return $db->fetchCol($query);
    }
  }

  /**
   * loadEntityMetadata
   *
   * Load the entity metadata for an entity
   * 
   * @param string $entityName The entity name
   * @return array 
   */
  protected function loadEntityMetadata($entityName)
  {
    if (! strlen($this->_entityTableName)) {
      throw \InvalidArgumentException('Missing entity table name');
    } 
    else { 
      $db = $this->getDatabaseAdapter();
      $query = $db->select()->from($this->_entityTableName)->where('entity_name = ?', $entityName);
      $result = $db->query($query)->fetch();
      
      if (empty($result)) {
        throw \Exception(sprintf('The entity metadata for entity %s was not found', $entityName));
      }
      $metadata = array(
        'entityName' => $result['entity_name'],
        'className' => $result['class_name'],
        'tableName' => $result['table_name'],
        'repositoryClassName' => $result['repository_class_name']
      );
      return $metadata;
    }
  }

  /**
   * loadFieldMetadata
   *
   * Load the field metadata for a given entity name
   * 
   * @param string $entityName The entity name
   * @return array  The field metadata
   */
  protected function loadFieldMetadata($entityName)
  {
    if (! strlen($this->_fieldTableName)) {
      throw \InvalidArgumentException('Missing field metadata table name');
    } 
    else { 
      $db = $this->getDatabaseAdapter();
      $query = $db->select()->from($this->_fieldTableName)->where('entity_name = ?', $entityName);
      $result = $db->query($query)->fetchAll();    
      
      $mappings = array();
      foreach($result as $mapping) {
        $mappings[] = array(
          'fieldName' => $mapping['field_name'],
          'columnName' => $mapping['column_name'],
          'identity' => $mapping['is_identity'],
          'dataType' => $mapping['data_type'],
          'dataLength' => $mapping['data_length'],
          'defaultValue' => $mapping['default_value']
        );
      }
    }
    return $mappings;
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
    if (! strlen($this->_associationTableName)) {
      throw \InvalidArgumentException('Missing field metadata table name');
    } 
    else { 
      $db = $this->getDatabaseAdapter();
      $query = $db->select()->from($tableName)->where('entity_name = ?', $entityName);
      $result = $db->query($query)->fetchAll();    

      $mappings = array();
      foreach($result as $mapping) {
        $mappings[] = array(
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
           ),
        );
      }
    }
    return $mappings;
  }

}