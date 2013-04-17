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
   * __construct
   *
   * @param Database\Adapter $dbAdapter      [description]
   * @param Driver\IDriver   $metadataDriver [description]
   */
  public function __construct(Driver\IDriver $driver)
  {
    $this->_driver = $driver;
    $this->_names = $driver->getAllEntityNames();

    return $this;
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
    return (in_array($entityName, $this->_names)) ? true : false;
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
  public function getEntityMetadata($entityName)
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
    return $this->_driver->populate($this, $entityName);
  }



}