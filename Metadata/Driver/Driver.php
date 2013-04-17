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
   * getEntityNames
   *
   * Return the entity names
   * 
   * @return 
   */
  public function getAllEntityNames()
  {
    if (empty($this->_entityNames)) {
      $this->_entityNames = $this->loadAllEntityNames();
    }
    return $this->_entityNames;
  }

  /**
   * getEntityMetadata
   *
   * Return the entity metadata for a given entity name
   * 
   * @param string $entityName The name of the entity owning metadata
   * @return array The entity's metadata
   */
  public function getEntityMetadata($entityName) 
  {
    return $this->loadMetadata(self::META_ENTITY, $entityName);
  }

  /**
   * getFieldMetadata
   *
   * Return the field metadata for a given entity name
   * 
   * @param string $entityName The name of the entity owning the fields
   * @return array The entity's field metadata
   */
  public function getFieldMetadata($entityName) 
  {
    return $this->loadMetadata(self::META_FIELDS, $entityName);
  }

  /**
   * getAssociationMetadata
   *
   * Return the association metadata for a given entity name
   * 
   * @param string $entityName The name of the entity owning the associations
   * @return array The entity's association metadata
   */
  public function getAssociationMetadata($entityName) 
  {
    return $this->loadMetadata(self::META_ASSOC, $entityName);
  }

  /**
   * loadMetadata
   *
   * Load the required metadata for a given entity name
   * 
   * @param string $type The type of metadata to retreve
   * @param string $entityName The name of the entity to load the metadata for
   * @return array $metadata The entity metadata of the desired type
   * @throws \InvalidArgumentException When the type is unknown
   * @throws \Exception If the entity name is not known to this driver
   */
  protected function loadMetadata($type, $entityName)
  {
    if (isset($this->_metadata[$type][$entityName]) && ! empty($this->_metadata[$type][$entityName])) {
      /* exit early if its already loaded **/
      return $this->_metadata[$type][$entityName];
    } 
    else if (in_array($entityName, $this->getAllEntityNames())) {
      switch($type) {
        case self::META_ENTITY:
          $metadata = $this->loadEntityMetadata($entityName);
        break;
        case self::META_FIELDS:
          $metadata = $this->loadFIeldMetadata($entityName);
        break;
        case self::META_ASSOC:
          $metadata = $this->loadAssociationMetadata($entityName);
        break;
        default:
          throw new \InvalidArgumentException(sprintf('Unknown metadata type %s', $type));
      }
      if (! isset($this->_metadata[$type])) {
        $this->_metadata[$type] = array();
      }
      $this->_metadata[$type][$entityName] = $metadata;

      return $metadata;
    } else {
      throw new \Exception(sprintf('Cannot find %s metadata for entity %s', $type, $entityName));
    }
  }

  /**
   * loadAllEntityNames
   *
   * Load all the entity names into an array
   * 
   * @return array Array of unique entity names
   */
  abstract protected function loadAllEntityNames();

  /**
   * loadEntityMetadata
   *
   * Load the metadata for one entity
   * 
   * @return array $metadata The entity metadata
   */
  abstract protected function loadEntityMetadata($entityName);

  /**
   * loadFieldMetadata
   *
   * Load the field metadata
   * 
   * @param array $entityName The entity name
   * @return array $metadata The entity's field metadata
   */
  abstract protected function loadFIeldMetadata($entityName);

  /**
   * loadAssociationMetadata
   *
   * Load the association metadata for a given entity name
   * 
   * @param string $entityName The entity name
   * @return array $metadata The entity's association metadata
   */
  abstract protected function loadAssociationMetadata($entityName);

}