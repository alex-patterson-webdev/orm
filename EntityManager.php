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
   * $_identityMap
   * 
   * @var Orm\IdentityMap
   */
  protected $_identityMap = null;

  /**
   * $_metadata 
   * 
   * @var array Loaded entity metadata instances
   */
  protected $_metadata = array();

  /**
   * $_persisters
   *
   * @var array Loaded persister instances
   */
  protected $_persisters = array();

  /**
   * $_repositories
   *
   * @var array Loaded entity repository instances
   */
  protected $_repositories = array();


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
   * getDatabaseAdapter()
   * 
   * @return [type] [description]
   */
  public function getDatabaseAdapter()
  {
    if (! isset($this->_dbAdapter)) {
      $this->_dbAdapter = new Database\Adapter();
    }
    return $this->_dbAdapter;
  }

  /**
   * getMetadataDriver
   * 
   * @return Orm\Metadata\Driver
   */
  protected function getMetadataDriver()
  {
    $database = $this->getDatabaseAdapter();
    $options = array(
      'entityTableName' => 'fw_metadata_entity',
      'fieldTableName' => 'fw_metadata_field',
      'associationTableName' => 'fw_metadata_association'
    );
    return new Metadata\Driver\DatabaseDriver($database, $options);
  }

  /**
   * getMetadataFactory
   *
   * @return Metadata
   */
  protected function getMetadataFactory()
  {
    if (! isset($this->_metadataFactory)) {
      $this->_metadataFactory = new Metadata\Factory($this->getMetadataDriver());
    }
    return $this->_metadataFactory;
  }

  /**
   * getEntityMetadata
   *
   * Return the entity metadata for a given entity name
   * 
   * @param  [type] $entityName The entity name
   * @return Orm\Metadata\EntityMetadata $metadata
   */
  public function getEntityMetadata($entityName)
  {
    if (! isset($this->_metadata[$entityName])) {
      $metadata = $this->getMetadataFactory()->getEntityMetadata($entityName);
      $this->metadata[$entityName] = $metadata;
    }
    return $this->metadata;
  }

  /**
   * getEntityPersisters
   *
   * Return an entity persister for the given entity name
   * 
   * @param string $entityName The entity name
   * @return Orm\Persister\Persister $persister
   */
  public function getEntityPersister($entityName)
  {
    if (! isset($this->_persisters[$entityName])) {
      $metadata = $this->getEntityMetadata($entityName);
      $this->_persisters[$entityName] = new Persister\Persister($metadata, $this);
    }
    return $this->_persisters[$entityName];
  }

  /**
   * getEntityRepository
   *
   * Return the entity repository class, used to execute specific
   * relationship SQL for the target entities
   * 
   * @param string $entityName The entity repository to load
   * @return Repository
   */
  public function getEntityRepository($entityName)
  {
    if (! isset($this->_repositories[$entityName])) {
      try {
        $metadata = $this->getEntityMetadata($entityName);
        $className = $metadata->getRepositoryClassName();
        $repository = new $className($metadata, $this);
        $this->_repositories[$entityName] = $repository;
      } catch (\Exception $e) {
        throw new \Exception('Unknown repository class name ' . $className .' for entity ' . $metadata->getEntityName());
      }
    }
    return $this->_repositories[$entityName];
  }

  /**
   * getIdentityMap
   *
   * Return the identity map instance
   * 
   * @return Orm\IdentityMap
   */
  protected function getIdentityMap()
  {
    return $this->_identityMap;
  }

  /**
   * setUnitOfWork
   *
   * Set the identity map instance
   * 
   * @param Orm\IdentityMap $identityMap The identity map instance
   */
  protected function setIdentityMap(Orm\IdentityMap $identityMap)
  {
    $this->_identityMap = $identityMap;
  }

  /**
   * isInIdenityMap
   *
   * Check if an entity is within the entity map
   * 
   * @param  array   $id The entity identity
   * @return boolean
   */
  public function isInIdentityMap($entityName, array $id)
  {
    return $this->getIdentityMap()->isInIdentityMap($entityName, $id);
  }

  /**
   * getIdentityMap
   *
   * Return the instance of the identity map
   * 
   * @return Orm\IdentityMap
   */
  public function getIdentityMap()
  {
    if (is_null($this->_identityMap)) {
      $this->_identityMap = new IdentityMap();
    }
    return $this->_identityMap;
  }

  /**
   * setIdentityMap
   *
   * Set the identity map instance
   * 
   * @param Orm\IdentityMap $identityMap The identity map
   */
  public function setIdentityMap(Orm\IdentityMap $identityMap)
  {
    $this->_identityMap = $identityMap;
  }

  /**
   * getFromIdentityMap
   *
   * Return an entity from the identity map
   * 
   * @param string $entityName The entity name
   * @param array $id  The entity identity
   * @return IEntity|null The entity instance
   */
  public function getFromIdentityMap($entityName, array $id)
  {
    return $this->getIdentityMap()->getFromIdentityMap($entityName, $id);
  }

  /**
   * addToIdentityMap
   *
   * Add an entity to the identity map
   * 
   * @param Orm\Entity\Abstract $entity The identity to add
   */
  public function addToIdentityMap(Orm\Entity\IEntity $entity)
  {
    $this->getIdentityMap()->addToIdentityMap($entity);
  }

  /**
   * removeFromIdentityMap
   *
   * Remove an entity from the identity map
   * 
   * @return boolean
   */
  public function removeFromIdentityMap($entityName, array $id)
  {
    return $this->getIdentityMap()->removeFromIdentityMap($entityName, $id);
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
    if (! is_array($id)) $id = array($id);
    if ($this->isInIdentityMap($entityName, $id)) {
      return $this->getFromIdentityMap($entityName, $id);
    } else {
      $repository = $this->getEntityRepository($entityName);
      $entity = $repository->findById($id);

      if ($entity instanceof $repository->getEntityClassName()) {
        return $entity;
      }
    }
    return null;
  }

  /**
   * findAll
   *
   * Return the entire collection of the given entity name
   * 
   * @return IEntityCollection
   */
  public function findAll($entityName)
  {
    return $this->getEntityRepository($entityName)->findAll();
  }

  /**
   * findOne
   *
   * Find one entity based on the provided
   * conditional criteria
   * 
   * @param  string $entityName  [description]
   * @param  array $conditional [description]
   * @return IEntity|null
   */
  public function findOne($entityName, $criteria)
  {
    return $this->getEntityRepository($entityName)->findOne($criteria);
  }

  /**
   * findMany
   *
   * Find a collection of entity instances by a set of
   * conditional criteria 
   * 
   * @param  string $entityName The entity name
   * @param  array $condition The collection conditions
   * @return IEntityCollection
   */
  public function findMany($entityName, $criteria)
  {
    return $this->getEntityRepository($entityName)->findMany($entityName, $criteria);
  }




  //public function save(Entity\IEntity $entity);

  //public function delete($entityName, $id);



}