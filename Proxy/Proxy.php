<?php

namespace Orm\Proxy;

use Orm\Entity;

/**
 * Proxy
 *
 * Base proxy class
 * 
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 */
class UserProxy extends Entity\User implements IProxy
{
    /**
     * Loader
     * 
     * Closure used to load the proxied intance
     * 
     * @param Entity\IEntity $proxy
     * @param string The method name called
     * @param  array $arguments The method arguments
     * 
     * @var Closure
     */
    public $loader;

    /**
     * Cloner
     * 
     * Closure used to clone the proxied intance
     * 
     * @param Entity\IEntity $proxy
     * @param string The method name called
     * @param  array $arguments The method arguments
     * 
     * @var Closure
     */
    public $cloner;

    /**
     * $loaded
     * 
     * @var boolean
     */
    public $loaded

    /**
     * $loazyFields
     *
     * @var array Fields to load lazy: fieldName => defaultValue
     */
    public static $lazyFields = array();

    /**
     * __construct
     *
     * @param  Closure $loader The loader Closure
     * @param  Closure $cloaner The cloaner Closure
     */
    protected function __construct(Closure $loader, Closure $cloner = null)
    {
      $this->loader = $loader;
      $this->cloner = $cloner;

      return $this;
    }

    /**
     * load
     *
     *  Loads the proxied instance with its data
     * 
     * @return void
     */
    public function load()
    {
      $this->loader->__invoke($this, 'load', array());
    }

    /**
     * isLoaded
     *
     * Check if the proxy has been loaded
     * 
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * setLoaded
     *
     * @return boolean
     */
    public function setLoaded()
    {
      return $this->loaded;
    }

    /**
     * setLoader
     *
     * Set the proxy loader Closure
     * 
     * @param Closure $loader The Closure
     */
    public function setLoader(\Closure $loader = null)
    {
      $this->loader = $loader;
    }

    /**
     * getLoader
     *
     * Return the loader Closure
     * 
     * @return Closure
     */
    public function getLoader()
    {
      return $this->loader;
    }

    /**
     * getLazyFields
     *
     * Return the laxy loaded field default values
     * 
     * @return array The map of lazy field values
     */
    public function getLazyFields()
    {
      return self::$lazyFields;
    }

}