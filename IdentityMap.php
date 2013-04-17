<?php

namespace Orm;

use Orm\Entity;

class IdentityMap
{
  /**
   * $_map
   *
   * @var array The identity map
   */
  protected $_map = array();

  /**
   * getIdentityMapKey
   *
   * Return the key for the entity map
   * 
   * @param  array  $id The identity map key
   * @return string The entity key   
   */
  protected function getIdentityMapKey(array $id)
  {
    return implode("-", $id);
  }

  /**
   * isInIdentityMap
   *
   * Check if a given entity is within the identity map
   * 
   * @param  string  $entityName The entity name
   * @param  array   $id                The entity identity
   * @return boolean
   */
  public function isInidentityMap($entityName, array $id)
  {
    $key = $this->getIdentityMapKey($id);
    if (isset($this->_map[$entityName]) && isset($this->_map[$enittyName][$key])) {
      return true;
    }
    return false;
  }

  /**
   * addToIndentityMap
   *
   * add an entity to the indentity map
   * 
   * @param string $entityName The entity name
   * @param array  $id  The entity identity
   */
  public function addToIndentityMap(Entity\IEntity $entity, array $id)
  {
    $key = $this->getIdentityMapKey($id);
    $entityName = $entity->getEntityName();
    
    if (! isset($this->_map[$entityName])) {
      $this->_map[$entityName] = array();
    }
    $this->_map[$entityName][$key] = $entity;
  }

  /**
   * removeFromIdentityMap
   *
   * Remove an entity from the identity map
   * 
   * @param  string $entityName The entity name
   * @param  array  $id               The entity identity
   * @return boolean If the remove was successful
   */
  public function removeFromIdentityMap($entityName, array $id)
  {
    if ($this->isInidentityMap($entityName, $id)) {
      $key = $this->getIdentityMapKey($id);
      unset($this->_map[$entityName][$id]);

      return true;
    }
    return false;
  }

}