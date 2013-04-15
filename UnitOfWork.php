<?php

namespace Orm;

use Orm\Entity;
use Orm\Metadata;

class UnitOfWork
{
  /**
   * $_entityManager
   * 
   * @var Orm\EntityManager
   */
  protected $_entityManager = null;

  /**
   * $_metadataFactory
   *
   * @var Orm\Metadata\Factory The entity metadata factory
   */
  protected $_metadataFactory = null;

  /**
   * $_identities
   * 
   * @var array
   */
  protected $_identities = array();

  /**
   * $_identityMap
   * 
   * @var Orm\IdentityMap 
   */
  protected $_identityMap = null;

  /**
   * getEntityManager
   *
   * Return the entity manager instance
   * 
   * @return EntityManager
   */
  public function getEntityManager()
  {
    return $this->_entityManager;
  }

  /**
   * setEntityManager
   *
   * Set the entity manager instance
   * 
   * @param EntityManager $entityManager
   */
  protected function setEntityManager(EntityManager $entityManager)
  {
    $this->_entityManager = $entityManager;
  }

  /**
   * getInstanceHash
   *
   * Return the hash id for a specific entity instance
   * 
   * @param  Orm\Entity\IEntity $entity The entity to obtain a hash from
   * @return string The instance id hash
   */
  protected function getEntityHash(Orm\Entity\IEntity $entity)
  {
    return spl_object_hash($entity);
  }

  /**
   * getEntityIdentity
   *
   * Resolve the identity of an entity by its hash id 
   * 
   * @param string $hash The identity hash
   * @return array The entity identity
   */
  protected function getEntityIdentity($hash) 
  {
    if (isset($this->_identities[$hash])) {
      return $this->_identities[$hash];
    } else {
      throw \InvalidArgumentException("The identity '$hash' is unknown to this unit of work");
    }
  }

  protected function setEntityIdentity(Entity\IEntity $entity)
  {
    
  }



  public function registerCleanEntity($entity)
  {
    $key = $this->getInstanceId($entity);
  }






  /**
   * tryById
   *
   * attempt to find the entity within the identity map,
   * if found return it or null if not
   * 
   * @param string $entityName The entity name
   * @param array  $id The entity identity
   * @return null|Orm\Entity\IEntity
   */
  public function tryById($entityName, array $id)
  {
    if ($this->isInIdentityMap($entityName, $id)) {
      return $this->getFromIdentityMap($entityName, $id);
    }
    return null;
  }

  






}