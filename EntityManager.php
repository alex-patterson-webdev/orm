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


    return $this;
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

  public function setDatabaseAdapter($dbAdapter)
  {
    $this->_dbAdapter = $dbAdapter;
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
      'entityTableName' => 'fw_entity',
      'fieldTableName' => 'fw_field',
      'associationTableName' => 'fw_association'
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
      //try {
        $metadata = $this->getEntityMetadata($entityName);
        $className = $metadata->getRepositoryClassName();
        $repository = new $className($metadata, $this);
        $this->_repositories[$entityName] = $repository;
      //} catch (\Exception $e) {
        //throw new \Exception('Unknown repository class name ' . $className .' for entity ' . $entityName);
      //}
    }
    return $this->_repositories[$entityName];
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
  public function addToIdentityMap(Orm\Entity\IEntity $entity, array $id)
  {
    $this->getIdentityMap()->addToIdentityMap($entity, $id);
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

      //if ($entity instanceof $repository->getEntityClassName()) {
        return $entity;
      //}
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

  /**
   * newEntity
   *
   * Create a new entity instance
   * 
   * @param  [type] $className [description]
   * @return [type]            [description]
   */
  public function newEntity($className)
  { 
    $entity = new $className();

    return $entity;
  }

  /**
   * createEntity 
   * 
   * @param  [type] $entityName [description]
   * @param  array  $data       [description]
   * @return [type]             [description]
   */
  protected function createEntity($entityName, array $data = array())
  {
    $metadata = $this->getEntityMetadata($entityName);
    $fields = $metadata->getIdentityFields();

    /** Build array of identity field values **/
    if ($metadata->hasCompositeIdentity()) {
      $id = array();
      foreach ($fields as $fieldName) {
        if (isset($data[$fieldName])) {
          $id[$fieldName] = $data[$fieldName];
        } else if ($metadata->hasForeignIdentity()) {
          $columnName = $metadata->getColumnForField($fieldName);
          if (isset($data[$columnName])) $id[$fieldName] = $data[$columnName];
        }
      }
    } else {
      $fieldName = $metadata->getSingleIdentityField();
      $id = array($fieldName => $data[$fieldName]);
    }

    $load = false;
    if ($this->isInIdentityMap($entityName, $id)) {
      $entity = $this->getFromIdentityMap($entityName, $id);
      if (! $entity->isLoaded()) $load = true;
    } else {
      $entity = $this->newEntity($metadata->getClassName());
      $this->addToIdentityMap($entity, $id);
      $load = true;
    }

    if (false == $load) {
      return $entity;
    }
    $entity->setLoaded(true);
    $entity->setEntityManager($this);

    /** Field Mappings  **/
    $mappings = $metadata->getFieldMappings();
    foreach ($data as $columnName => $value) {
      $fieldName = $metadata->getField($columnName);
      if (isset($mappings[$fieldName])) {
        $metadata->getReflectionField($fieldName)->setValue($entity, $value);
      }
    }
    /** Association mappings **/
    $associations = $metadata->getAssociationMappings();
    foreach ($associations as $assoc) {
      $targetMetadata = $this->getEntityMetadata($assoc['targetEntityName']);

      switch($targetMetadata['type']) {
        case Metadata\EntityMetadata::ASSOC_ONE_TO_ONE:
          if ($assoc['isOwningSide']) {

            foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $sourceColumn) {
              if (isset($data[$sourceColumn])) {
                $assocFieldName = $targetMetadata->getField($targetColumn);
                $assocId[$assocFieldName] = $data[$sourceColumn]; 
              }
            }
            if (empty($assocId)) {
              $metadata->getReflectionField($assoc['fieldName'])->setValue($entity, null);
            } else {
              if ($this->isInIdentityMap($assoc['targetEntityName'], $assocId)) {
                $targetEntity = $this->getFromIdentityMap($assoc['targetEntityName'], $assocId);
              } else {
                if ($assoc['loadType'] == Entity\EntityMetadata::LOAD_EAGER) {
                  $targetEntity = $this->findById($assoc['targetEntityName'], $assocId);
                } else {
                  $targetentity = $this->newEntity($targetMetadata->getClassName());
                  $this->addToIdentityMap($entity, $assocId);
                }
              }
              $metadata->getReflectionField($assoc['fieldName'])->setValue($targetEntity, $targetEntity); 
            }
          } else {
            $targetEntity = $this->getEntityPersister($assoc['targetEntityName'])->findOneToOneEntity($assoc, $entity);
            $metadata->getReflectionField($assoc['fieldName'])->setValue($targetEntity, $targetEntity);
          }
        break;
        default:
          $collection = new Entity\EntityCollection($assoc['targetEntityName'], $this, new Collection());
          $collection->setOwner($entity, $assoc);
          $metadata->getReflectionField($assoc['fieldName'])->setValue($entity, $collection);

          if ($assoc['loadType'] == Entity\EntityMetadata::LOAD_EAGER) {
            $this->loadCollection($collection);
          }
      }
    }
    return $entity;
}






}