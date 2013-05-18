<?php

namespace Orm\Entity;

use Orm;
use Orm\Metadata;

abstract class Proxy implements IProxy
{
  /**
   * $_loaded
   *
   * The loaded state of the proxy
   * 
   * @var boolean
   */
  protected $_loaded = false;

  /**
   * $_proxy
   *
   * The proxied entity instance
   * 
   * @var Orm\Entity\IEntity
   */
  protected $_proxy;  

  /**
   * setLoaded
   *
   *  Set the loaded Proxy state
   * 
   * @param boolean $loaded The loaded state
   */
  public function setLoaded($loaded)
  {
    $this->_loaded = ($loaded) ? true : false;
  }

}