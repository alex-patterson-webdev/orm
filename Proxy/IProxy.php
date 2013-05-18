<?php

namespace Orm\Proxy;

/**
 * IProxy
 *
 * @author Alex Patterson
 */
interface IProxy
{
    /**
     * load
     * 
     * Initialize the proxy
     *
     * @return void
     */
    public function load();

    /**
     * isLoaded
     * 
     * Check if the proxy has been initilized
     *
     * @return bool
     */
    public function isLoaded();
}