<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Factory;

use Gush\Adapter\Adapter;
use Gush\Adapter\Configurator;
use Gush\Config;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * AdapterFactory create new Adapter and Configurator instances.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class AdapterFactory
{
    /**
     * @var array[]
     */
    private $adapters = [];

    /**
     * @param string   $name
     * @param callback $adapterFactory
     * @param callback $adapterConfigurator
     *
     * @return AdapterFactory
     *
     * @throws \RuntimeException
     */
    public function registerAdapter($name, $adapterFactory, $adapterConfigurator)
    {
        if (isset($this->adapters[$name])) {
            throw new \RuntimeException(sprintf('An adapter with name "%s" is already registered.', $name));
        }

        $this->adapters[$name] = [$adapterFactory, $adapterConfigurator];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAdapter($name)
    {
        return isset($this->adapters[$name]);
    }

    /**
     * @return array[]
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * Creates a new Adapter object with the given Configuration.
     *
     * @param string $name
     * @param array  $adapterConfig
     * @param Config $globalConfig
     *
     * @return Adapter
     *
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function createAdapter($name, array $adapterConfig, Config $globalConfig)
    {
        if (!isset($this->adapters[$name])) {
            throw new \RuntimeException(
                sprintf('No Adapter with name "%s" is registered.', $name)
            );
        }

        $adapter = $this->adapters[$name][0]($adapterConfig, $globalConfig);

        if (!$adapter instanceof Adapter) {
            throw new \LogicException(
                sprintf(
                    'Adapter-Factory callback is expected to return a Gush\Adapter\Adapter instance, got "%s" instead.',
                    is_object($adapter) ? get_class($adapter) : gettype($adapter)
                )
            );
        }

        return $adapter;
    }

    /**
     * Creates a new Configurator instance for the given adapter.
     *
     * @param string    $name      Name of the adapter (must be registered)
     * @param HelperSet $helperSet HelperSet object
     *
     * @return Configurator
     *
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function createAdapterConfiguration($name, HelperSet $helperSet)
    {
        if (!isset($this->adapters[$name])) {
            throw new \RuntimeException(
                sprintf('No Adapter with name "%s" is registered.', $name)
            );
        }

        $configurator = $this->adapters[$name][1]($helperSet);

        if (!$configurator instanceof Configurator) {
            throw new \LogicException(
                sprintf(
                    'Configurator-Factory callback is expected to return a Gush\Adapter\Configurator instance, got "%s" instead.',
                    is_object($configurator) ? get_class($configurator) : gettype($configurator)
                )
            );
        }

        return $configurator;
    }
}
