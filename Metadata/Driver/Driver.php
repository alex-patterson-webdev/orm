<?php

namespace Orm\Metadata\Driver;

use Orm\Entity;
use Orm\Database;
use Orm\Metadata;

/**
 * Driver
 *
 * Abstract metadata driver implmentation
 * 
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 */
abstract class Driver implements IDriver
{
  /**
   * Metadata types
   */
  const META_ENTITY = 'entity';
  const META_FIELDS = 'fields';
  const META_ASSOC = 'assoc';
  const META_NAMES = 'names';

  /**
   * $_entityNames
   *
   * @var array All the mapped entity names
   */
  protected $_entityNames = array();

  /**
   * $_entities
   * 
   * @var array All entity metadata
   */
  protected $_metadata = array();

  /**
   * isValidEntityName
   *
   * Check if the provided entity name is one that
   * this driver has metadata for
   * 
   * @return boolean If the entity name is valid
   */
  protected function isValidEntityName($entityName)
  {
    if (empty($this->_entityNames)) {
      $this->_entityName
    }
    return $this->_entityNames()
  }


  /**
   * loadMetadata
   *
   * Load the required metadata for a given entity
   * 
   * @param string $type   The type of metadat to retreve
   * @param string $entityName The name of the entity
   * @return array $metadata The entity metadata of desired type
   */
  protected function loadMetadata($type, $entityName)
  {
    if ($this->isValidEntityName($entityName)) {
      switch($type) {
        case self::META_ENTITY:
          $metadata = $this->loadEntityMetadata($entityName);
        break;
        case self::META_FIELDS:
          $metadata = $this->loadFIeldMetadata($entityName);
        break;
        case sef:META_ASSOC:
          $metadata = $this->loadAssociationMetadata($entityName);
        break;
        default:
          throw new \InvalidArgumentException(sprintf('Unknown metadata type %s', $type));
      }
      if (! isset($this->_metadata[$type])) $this->_metadata[$type] = array();
      $this->_metadata[$type][$entityName] = $metadata;
    } else {
      throw new \Exception(sprintf('Cannot find %s metadata for entity %s', $type, $entityName));
    }
  }



  /**
   * getMetadata
   *
   * Return the entity metadata for a given entity name
   * 
   * @param string $entityName The entity name
   * @return array $metadata The entity metadata
   */
  public function getEntityMetadata($entityName)
  {
    if (isset($this->_metadata['entity'][$entityName])) {
      $this->loadMetadata('entity', $entityName);
    }
    return $this->_metadata['entity'][$entityName];
  }







  abstract protected function loadEntityNames();

  public function getEntityNames()
  {
    return $this->_entityNames;
  }


  public function getEntities()
  {

  }


  abstract protected function loadEntities();

  abstract protected function loadFields();

  abstract protected function loadAssociations();

}