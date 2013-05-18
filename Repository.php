<?php

namespace Orm;

use Orm\Entity;
use Orm\Metadata;

/**
 * Orm\Repository
 * 
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 */
class Repository
{
  /**
   * $_entityName
   *
   * @var string The mapped entity name
   */
  protected $_entityName = '';

  /**
   * $_className
   * 
   * @var string The fully qualified class name string
   */
  protected $_className = '';

  /**
   * $_metadata
   *
   * Instance of the entity metadata
   * 
   * @var Orm\Metadata\EntityMetadata
   */
  protected $_metadata = null;

  /**
   * $_entityManager
   *
   * The entity manager instance
   * 
   * @var Orm\EntityManager
   */
  protected $_entityManager = null;

  /**
   * __construct
   *
   * Public constructor
   * 
   * @param  Metadata\EntityMetadata $metadata The metadata of the repository
   * @param  EntityManager $em An entity manager instance
   * @return Orm\Repository
   */
  public function __construct(Metadata\EntityMetadata $metadata, EntityManager $em)
  {
    $this->_entityName    = $metadata->getEntityName();
    $this->_className     = $metadata->getClassName();
    $this->_metadata      = $metadata;
    $this->_entityManager = $em;

    return $this;
  }

  /**
   * getEntityName
   *
   * Return the name of the entity
   * 
   * @return string The name of the entity
   */
  public function getEntityName()
  {
    return $this->_entityName;
  }

  /**
   * getEntityClassName
   *
   * Return the name of the mapped entity class
   * 
   * @return string The fully qualified class name
   */
  public function getEntityClassName()
  {
    return $this->_className;
  }

  /**
   * findById
   *
   * Find an entity by its identity. We need to ensure that the
   * identity map is checked here as well as within the EntityManager.
   * 
   * @param  array  $id The idenity to find
   * @return  IEntity|null
   */
  public function findById(array $id)
  {
    if ($this->_entityManager->isInIdentityMap($this->_entityName, $id)) {
      return $this->_entityManager->getFromIdentityMap($this->_entityName, $id);
    } else {
      $fields = array();
      foreach($this->_metadata->getIdentityFields() as $key => $fieldName) {
        $fields[$fieldName] = $id[$key];
      }
      return $this->_entityManager->getEntityPersister($this->_entityName)->loadOne($fields);
    }
  }

  /**
   * findOne
   *
   * Find one entity instance with given criteria
   * 
   * @param  array  $criteria Array containing the field names to 
   * desired field values criteria
   * @return mixed(Orm\Entity|null) $entity 
   */
  public function findOne(array $criteria = array())
  {
    $persister = $this->_entityManager->getEntityPersister($this->_entityName);

    return $persiter->loadOne($criteria);
  }

  /**
   * findMany
   *
   * Find many entities by using the provided criteria
   * 
   * @return array 
   */
  public function findMany(array $criteria)
  {
    $persister = $this->_entityManager->getEntityPersister($this->_entityName);

    return $persiter->loadMany($criteria);
  }

  /**
   * __call
   *
   * Catches calls to non-existing methods.
   * 
   * @param  string $method    The name of the missing method
   * @param  array  $arguments Array of arguments passed to the non-existing method
   * @return 
   */
  public function __call ($method, $arguments)
  {
    if (substr($method, 0, 9) == 'findmanyby') {
      $methodName = 'findManyBy';
      $fieldName  = substr($method, 10, strlen($method));
    } else if (substr($method, 8) == 'findoneby') {
      $methodName = 'findOneBy';
      $fieldName  = substr($method, 9, strlen($method));
    } else {
      throw new \BadMethodCallException(
        sprintf('Fatal error: Unable to call invalid method "%s" in class "%s"', $method, __CLASS__)
      );
    }
    if ($this->_metadata->hasField($fieldName) || $this->_metadata->hasAssociation($fieldName)) {
      return call_user_func_array($this->$methodName, $arguments);
    }
    throw new \BadMethodCallException(sprintf('Fatal error: Unable to call invalid method "%s" in class "%s"', $method, __CLASS__));
  }

}