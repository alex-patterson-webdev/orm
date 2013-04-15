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
    $this->_entityName = $metadata->getEntityName();
    $this->_metadata = $metadata->getClassName();
    $this->_metadata = $metadata;
    $this->_entityManager = $em;

    return $this;
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
   * Find an entity by its identity
   * 
   * @param  array  $id The idenity to find
   * @return  IEntity|null
   */
  public function findById(array $id)
  {
    /** Check the identity map first **/
    $em = $this->getEntityManager();
    if ($em->isInIdentityMap($this->_entityName, $id)) {
      $entity = $em->getFromIdentityMap($this->_entityName, $id);  
      return ($entity instanceof $this->_className) ? $entity : null;
    }
    /** Generate the identity criteria **/
    $criteria = array();
    $fields = $this->_metadata->getIdentityFields();
    for ($x = 0; $x < count($fields); $x++) {
      $criteria[$fields[$x]] = $id[$x];
    }
    return $em->getEntityPersister($this->_entityName)->loadOne($criteria);
  }

  /**
   * findOne
   *
   * Find one entity instance withgiven criteria
   * 
   * @param  array  $criteria [description]
   * @return [type]           [description]
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
   * Magic PHP __call allows catching of 'FindByXXX' method calls
   * 
   * @param  string $method The name of the method that was called
   * @param  array $args array of arguments passed to the method
   * @return Orm\Entity\ICollection $collection
   */
  public function __call($method, $args)
  {
    $method = strtolower($method);
    if (substr($method, 0, 9) == 'findmanyby') {
      $by = substr($method, 10, strlen($method));
      $act = 'findManyBy';
    } else if (substr($method, 8) == 'findoneby') {
      $by = substr($method, 9, strlen($method));
      $act = 'findOneBy';
    } else {
      throw new \Exception('Unknown entity fetch method ' . $method .' for entity repository '. __CLASS__);
    }
    $fieldName = strtolower($by);
    if ($this->_metadata->hasField($fieldName) || $this->metadata->hasAssociation($fieldName)) {
      $params = array($fieldName => $args[0]);

      return $act($params);
    }
    throw \Exception('Unknown method ' . $method .' called for class ' . __CLASS__);
  }

}