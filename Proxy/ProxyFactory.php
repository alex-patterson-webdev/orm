<?php

namespace Orm\Proxy;

use Orm;
use Orm\Metadata;
use Orm\Entity;

class ProxyFactory
{
  /**
   * $_entityManager
   *
   * The entity manager instance
   *  
   * @var Orm\EntityManager
   */
  protected $_entityManager;

  /**
   * $_metadataFactory
   * 
   * @var Orm\Metadata\Factory
   */
  protected $_metadataFactory;

  /**
   * $_proxyMetadata
   * 
   * @var array Loaded proxy metadata instances
   */
  protected $_proxyMetadata = array();

  /**
   * __construct
   *
   * initilize the proxy factory
   *  
   * @param Metadata\Factory $metadata [description]
   */
  public function __construct(EntityManager $em, Metadata\Factory $mdf, $proxyNamespace = '')
  {
    $this->_entityManager = $em;
    $this->_metadataFactory = $mdf;
    $this->_proxyNamespace = 'Orm\Proxy';

    return $this;
  }

  /**
   * getProxy
   *
   * Return a new proxied instance of the given class name
   * 
   * @param string $className The class to proxy
   * @param array  $id The identity of the instance
   * @return IProxy The new proxied class instance
   */
  public function getProxy($entityName, array $id)
  {
    $metadata = $this->getProxyMetadata($entityName);
    $className = $metadata->getProxyClassName();
    $proxy = new $className($metadata->loader, $metadata->cloner);

    foreach($metadata->identityFields as $fieldName) {
      $metadata->reflectionFields[$fieldName]->setValue($proxy, $id[$fieldName]);
    }

    return $proxy;
  }

  /**
   * getProxyMetadata
   *
   * Create new proxy metadata instance from the given entity
   * name
   * 
   * @param string $enittyName The entity name
   */
  protected function getProxyMetadata($entityName)
  {
    if (! isset($this->_proxyMetadata[$entityName]) {
      $metadata = $this->_entityManager->getEntityMetadata($entityName);
      $persister = $this->_entityManager->getEntityPersister($entityName);
      $className = $this->generateProxyClassName($entityName, $this->_proxyNamespace);

      $this->_proxyMetadata[$enittyName] = new ProxyMetadata(
        $className, 
        $metadata->getIdentityFields(), 
        $metadata->getReflectionFields(),
        $this->generateLoader($metadata, $persister),
        $this->generateCloner($metadata, $persister)
      );
    }
    return $this->_proxyMetadata[$entityName];
  }

  /**
   * generateProxyClassName
   *
   * Make a new proxy class name using the provided entity name
   * 
   * @param  string $entityName The entity name
   * @param  string $proxyNamespace The proxy namespace
   * @return string The new proxy class name
   */
  protected function generateProxyClassName($entityName, $proxyNamespace)
  {
    return $proxyNamespace .'\\' . 'Proxy' . $entityName;
  }

  /**
   * generateLoader
   *
   * Creates a new Closure designed to load the target Proxy instance
   * 
   * @param  EntityMetadata $metadata  The entity metadata
   * @param  Persister $persister The entity perister instance
   * @return Closure
   */
  protected function generateLoader(EntityMetadata $metadata, Persister $persister)
  {
    return function(Proxy $proxy) use ($persister, $metadata)
    {
      $proxy->_setLoader(null);
      $proxy->_setCloner(null);

      if ($proxy->_isLoaded()) {
        return;
      }
      $lazyFields = $proxy->getLazyFields();

      foreach($lazyFields as $fieldName => $value) {
        if (! isset($proxy->$fieldName)) {
          $proxy->$fieldName = $lazyFields[$fieldName];
        }
      }
      $proxy->setLoaded(true);
    
      if (null == $persister->load($metadata->getIdentityValues($proxy), $proxy)) {
        throw new \Exception('The entity was not found');
      }
    }; 
  }

  /**
   * generateCloner
   *
   * Create a new cloner instance
   * 
   * @param  EntityMetadata $metadata The entity metadata
   * @param  Persister $persister The entity persister
   * @return   Closure
   */
  protected function generateCloner(EntityMetadata $metadata, Persister $persister)
  {
    return function(Proxy $proxy) use ($persister, $metadata) {
      if ($proxy->_isLoaded()) return;
      
      $proxy->_setLoaded(true);
      $proxy->_setLoader(null);

      $original = $persister->load($metadata->getIdentityValues($proxy));

      if (null === $original) {
        throw new \Exception('The entity was not found');
      }

      foreach($metadata->getReflectionClass()->getProperties() as $field) {
        $fieldName = $field->getName();
        if ($metadata->hasField($fieldName) || $metadata->hasAssociation($fieldName)) {
          $field->setAccessible(true);
          $field->setValue($proxy, $field->getValue($original));
        }
      }
    };
  }

}
