<?php

namespace Orm;

use Orm\Entity;
use Orm\Persister;
use Orm\Metadata;

/**
 * EntityManager
 *
 *  Client code API for accessing the in memory object graph and delegating
 *  the persistance of entity instance to the database
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 */
class EntityManager
{
  /**
   * $_entityFactory
   *
   * @var Orm\Factory
   */
  protected $_factory = null;

  /**
   * $_unitOfWork
   * 
   * @var Orm\UnitOfWork
   */
  protected $_unitOfWork = null;

  /**
   * __construct
   *
   * @param 
   */
  public function __construct()
  {

  }

  /**
   * getFactory
   *
   * Return the factory instance
   * 
   * @return Orm\Factory The factory instance
   */
  public function getFactory()
  {
    return $this->_factory;
  }

  /**
   * setFactory
   *
   * Set the factory instance
   *
   * @param Orm\Factory $factory The factory instance to set
   */
  public function setFactory(Orm\Factory $factory)
  {
    $this->_factory = $factory;
  }

  /**
   * getUnitOfWork
   *
   * Return the unit of work instance
   * 
   * @return Entit
   */
  protected function getUnitOfWork()
  {
    return $this->_unitOfWork;
  }

  /**
   * setUnitOfWork
   *
   * Set the unit of work instance
   * 
   * @param Orm\UnitOfWork $unitOfWork The unit of work instance
   */
  protected function setUnitOfWork(Orm\UnitOfWork $unitOfWork)
  {
    $this->_unitOfWork = $unitOfWork;
  }

  /**
   * createEntity
   *
   * Return a new entity instance
   * 
   * @param string $entityName The entity name
   * @return Orm\Entity\IEntity
   */
  public function createEntity($entityName)
  {
    return $this->getFactory()->createEntity($entityName, $this);
  }

  /**
   * findById
   *
   * Return an entity by its identity
   * 
   * @param  string $entityName [description]
   * @param  mixed $id
   * @return Orm\Entity\IEntity $entity
   */
  public function findById($entityName, $id)
  {
    $uow = $this->getUnitOfWork();
    $entity = $uow->tryGetById($entityName);

    if (! is_null($entity)) {
      return $entity;
    } else {
      $entity = $uow->findById($entityName, $id);
    }
    return $entity;
  }





  public function findMany($entityName, $condition);

  public function save(Entity\IEntity $entity);

  public function delete($entityName, $id);



}