<?php

namespace Orm\Entity;

use Orm;
use Orm\Metadata;

/**
 * Entity
 *
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 */
abstract class Entity implements IEntity
{
    /**
     * @var Orm\EntityManager
     */
    private static $entityManager;

    /**
     * @var ClassMetadata
     */
    private $metadata;

    /**
     * getEntityManager
     *
     * Return the entity manager instance
     * 
     * @return Orm\EntityManager $entityManager
     */
    public function getEntityManager()
    {
      return self::$entityManager;
    }

    /**
     * setEntityManager
     *
     * Set the entity manager responsible for all persistent object base classes.
     *
     * @param EntityManager $entityManager
     */
    static public function setEntityManager(EntityManager $em = null)
    {
        self::$entityManager = $em;
    }

    /**
     * injectEntityManager
     *
     * Inject the entity manager and entity metadata
     * 
     * @param  EntityManager  $em           The EntityManager instance
     * @param  EntityMetadata $metadata EntityMetadata instance
     * @return void
     */
    public function injectEntityManager(EntityManager $em, EntityMetadata $metadata)
    {
      if (self::$entityManager !== $em) {
        throw new InvalidArgumentException('The entity manager has already been defined for this entity');
      }
      $this->_metadata = $metadata;
    }

    /**
     * init
     *
     * Initlize the entity
     * 
     * @return void
     */
    public function init()
    {
      if (! is_null($this->_metadata)) return;
      if (null == self::$entityManager) {
        throw new \BadMethodCallException('The entity manager instance has not been defined');
      }
      $this->_metadata = self::$entityManager->getEntityMetadata(get_class($this));
    }

    /**
     * get
     *
     * @param  string $field The name of the field
     */
    protected function get($field)
    {
      $this->init();

      if ($this->_metadata->hasField($field) || $this->_metadata->hasAssociation($field)) {
        return $this->$field;
      }
      throw new \BadMethodCallException(
        sprintf('Unknown field name % for entity %', $field, $this->_metadata->getEntityName())
      );
    }

    /**
     * set
     *
     * Set the value of an entity field
     * 
     * @param string $field The name if the field to set
     * @param string $value The new field value
     */
    protected function set($field, $value)
    {
      $this->init();
      
      if ($this->_metadata->hasField($field) && ! $this->_metadata->isIdentityField($field)) {
        $this->$field = $value;
      } else if ($this->_metadata->hasAssociation($field) && $this->_metadata->isSingleIdentityAssociation($field)) {
        $target = $this->_metadata->getAssociationTargetClass($field);
        if (! ($value instanceof $target) && null !== $value) {
          throw new \InvalidArgumentException(sprintf('Expected entity instance %s', $target));
        }
        $this->$field = $value;
        $this->completeOwningSide($field, $target, $value);
      } else {
        throw new \BadMethodCallExceptio(
          sprintf('Unknown field %s for entity %s', $field, $this->_metadata->getEntityName())
        );
      }
    }

    /**
     * completeOwningSide
     *
     * Add this entity instance to the owning side of the association. To avoid  an
     * infinate loading loop, this is only done on the inverside of the relationship
     * 
     * @param  string $field  The field name holding the association
     * @param  string $targetClassName The target class name
     * @param  Orm\Entity\IEntity $target          The target entity instance
     * @return   void
     */
    public function completeOwningSide($field, $targetClassName, $target)
    {
      if ($this->_metadata->isAssociationInverseSide($field)) {
        $mappedByField = $this->_metadata->getAssociationMappedByTargetField($field);
        $metadata = self::$entityManager->getEntityMetadata($targetClassName);

        $method = ($metadata->isCollectionValuedAssociation($field) ? 'add' : 'set') . $mappedByField;
        $target->$method($this); 
      }
    }

    /**
     * add
     *
     *  Add a element to a collection
     * 
     * @param string $field The field name that holds the collecton
     * @param mixed $value The new element to add to the collection
     */
    protected function add($field, $value)
    {
      $this->init();

      if ($this->_metadata->hasAssociation($field) && $this->_metadata->isCollectionValuedAssociation($field)) {
        $targetClassName = $this->_metadata->getAssociationTargetClass($field);
        if (! $value instanceof $targetClassName) {
          $className = $this->_metadata->getClassName();
          throw new \InvalidArgumentException(
            sprintf('Cannot add invalid class value to entity %s. Excpecting class of type %s', $className, $targetClassName)
          );
        }
        if (! ($this->$field instanceof ICollection)) {
          $this->$field = new ArrayCollection($this->$field ?: array());
        }
        $this->$field->add($value);
        $this->completeOwningSide($field, $targetClassName, $value);
      } else {
        throw \BadMethodCallException(
          sprintf('The method %s cannot be found for entity %s', 'add' . $field, $this->_metadata->getEntityName())
        );
      }
    }

    /**
     * __call
     *
     * Magic method for catching the calls to set/set/add
     * Any calls to methods that do not match result in an execption
     * being thrown
     * 
     * @param string $method The non existing method name caught
     * @param  array $arguments The arguments passed to the method
     * @return mixed(any|void) 
     */
    public function __call($method, $arguments)
    {
      $command = substr($method, 0, 3);
      $fieldName = lcfirst(substr($method, 3));

      if ($command == 'set') {
        $this->set($fieldName, $arguments[0]);
      } else if ($command == 'get') {
        $this->get($fieldName);
      } else if ($command == 'add') {
        $this->add($fieldName, $arguments[0]);
      } else {
        throw \BadMethodCallException(
          sprintf('The method %s cannot be found for entity %s', $command . $fieldName, $this->_metadata->getEntityName())
        );
      }
    }

}