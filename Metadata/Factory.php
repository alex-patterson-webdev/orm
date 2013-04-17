<?php

namespace Orm\Metadata;

use Orm\Database\Adapter;
use Orm\Metadata\Driver as Driver;

class Factory
{
  /**
   * $_driver
   *
   * @var Orm\Metadata\Driver
   */
  protected $_driver = null;

  /**
   * $_metadata
   * 
   * @var array
   */
  protected $_metadata = array();

  /**
   * __construct
   *
   * @param Database\Adapter $dbAdapter      [description]
   * @param Driver\IDriver   $metadataDriver [description]
   */
  public function __construct(Driver\IDriver $driver)
  {
    $this->_driver = $driver;
    $this->init();

    return $this;
  }

  /**
   * init
   *
   * Load the entity metadata for a given entity
   * 
   * @return void
   */
  protected function init()
  {
    foreach($driver->getAllEntityNames() as $name) {
      $this->_metadata[$name] = $driver->getEntityMetadata($name);
    }
  }

  /**
   * hasMetadataFor
   *
   * Check if this entity metadata has been defined
   * 
   * @param  [type]  $entityName [description]
   * @return boolean             [description]
   */
  protected function hasMetadataFor($entityName)
  {
    return (isset($this->_metadata[$entityName])) ? true : false;
  }

  /**
   * getEntityMetadata
   *
   * Return the entity metadata instance for a given entity name
   * The instance will be create is not already
   * 
   * @param  [type] $entityName [description]
   * @return [type]             [description]
   */
  public function createEntityMetadata($entityName)
  {
    if (! isset($this->_metadata[$entityName])) {
      $this->_metadata[$entityName] = $this->loadEntityMetadata($entityName);
    }
    return $this->_metadata[$entityName];
  }

  /**
   * newMetadata
   *
   * Return a new metadata instance
   * 
   * @param  [type] $className [description]
   * @param  array  $options   [description]
   * @return [type]            [description]
   */
  public function newMetadata($className, array $metadata = array())
  {
    return new EntityMetadata($className, $metadata);
  }

  /**
   * loadEntityMetadata
   *
   * Load the entity metadata for a given entity name
   * 
   * @param  [type] $entityName [description]
   * @return [type]             [description]
   */
  protected function loadEntityMetadata($entityName)
  {
    if (! $this->hasMetadataFor($entityName)) {
      throw new \InvalidArgumentException('Cannot fetch metadata for unknown entity ' . $entityName);
    }
    $data = $this->_driver->getEntityMetadata($entityName); 
    $metadata = new EntityMetadata($data['className'], $data);

    $fields = $this->_driver->getFieldMappings($entityName);
    foreach($fields as $fieldMapping) {
      $metadata->addFieldMapping($fieldMapping);
    }
    $assoc = $this->_driver->getAssociationMappings($entityName);
    foreach($assoc as $assocMapping) {
      $metadata->addAssociationMapping($assoc);
    }
    
    return $metadata;
  }



}