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
    foreach($this->_driver->getAllEntityNames() as $name) {
      $this->_metadata[$name] = array(
        'entity' => $this->_driver->getEntityMetadata($name),
        'fields' => $this->_driver->getFieldMetadata($name),
        'assoc' => $this->_driver->getAssociationMetadata($name)
      );
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
   * Load the entity metadata for a given entity name
   * 
   * @param string $entityName The name of the metadata entity
   * @return  EntityMetadata The loaded entity metadata instance
   */
  public function getEntityMetadata($entityName)
  {
    if (! $this->hasMetadataFor($entityName)) {
      throw new \InvalidArgumentException('Cannot fetch metadata for unknown entity ' . $entityName);
    }

    $entityMapping = $this->_metadata[$entityName]['entity']; 
    $metadata = new EntityMetadata($entityMapping['className'], $entityMapping);

    foreach($this->_metadata[$entityName]['fields'] as $fieldMapping) {
      $metadata->addFieldMapping($fieldMapping);
    }
    foreach($this->_metadata[$entityName]['assoc'] as $assocMapping) {
      $metadata->addAssociationMapping($assocMapping);
    }

    return $metadata;
  }

}