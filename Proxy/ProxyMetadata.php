<?php

namespace Orm\Proxy;

use Orm;
use Orm\Entity;
use Orm\Metadata;

class ProxyMetadata
{
  public $className;

  public $identityFields = array();

  public $reflectionFields = array();

  public $loader;

  public $cloner;

  /**
   * __construct
   *
   * Initilize the Proxy metadata
   * 
   * @param string $className The proxy class name
   * @param array  $identityFields  Map of the Proxys identity fields
   * @param array  $reflectionFields The proxy reflection fields
   * @param Closure $loader           Closure function for loading the Proxy
   * @param Closure $cloner           [description]
   */
  public function __construct($className, array $identityFields, array $reflectionFields, $loader, $cloner)
  {
    $this->className = $className;
    $this->identityFields = $identityFields;
    $this->reflectionFields = $reflectionFields;
    $this->loader = $loader;
    $this->cloner = $cloner;

    return $this;
  }

  /**
   * getProxyClassName
   *
   * Return the class name of proxy 
   * 
   * @return string The class name
   */
  public function getProxyClassName()
  {
    return $this->className;
  }

}