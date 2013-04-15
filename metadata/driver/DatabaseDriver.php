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
      $this->_entityMetadata[$entityName] = $this->loadMetadata($query);
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
  protected function loadAssociationMetadata()
  {
    if (! isset($this->_associationMetadata[$entityName])) {
      $tableName = $this->getAssociationTableName();
      $query = $this->createQuery($tableName)->where('entity_name = ?', $entityName);
      $this->_associationMetadata[$entityName] = $this->loadMetadata($query);
    }
    return $this->_associationMetadata[$entityName];
  }


}