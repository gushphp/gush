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
use Gush\Adapter\IssueTracker;
use Gush\Config;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * AdapterFactory creates new Adapter and Configurator instances.
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
     * @var array[]
     */
    private $issueTrackers = [];

    /**
     * @param string   $name
     * @param callback $adapterFactory
     * @param callback $adapterConfigurator
     *
     * @return AdapterFactory
     *
     * @throws \InvalidArgumentException
     */
    public function registerAdapter($name, $adapterFactory, $adapterConfigurator)
    {
        if (isset($this->adapters[$name])) {
            throw new \InvalidArgumentException(sprintf('An adapter with name "%s" is already registered.', $name));
        }

        $this->adapters[$name] = [$adapterFactory, $adapterConfigurator];
    }

    /**
     * Returns whether adapter by name is registered.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAdapter($name)
    {
        return isset($this->adapters[$name]);
    }

    /**
     * Returns registered adapters.
     *
     * @return array[]
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * @param string   $name
     * @param callback $issueTrackerFactory
     * @param callback $issueTrackerConfigurator
     *
     * @return AdapterFactory
     *
     * @throws \InvalidArgumentException
     */
    public function registerIssueTracker($name, $issueTrackerFactory, $issueTrackerConfigurator)
    {
        if (isset($this->issueTrackers[$name])) {
            throw new \InvalidArgumentException(
                sprintf('An issue tracker with name "%s" is already registered.', $name)
            );
        }

        $this->issueTrackers[$name] = [$issueTrackerFactory, $issueTrackerConfigurator];
    }

    /**
     * Returns whether issue tracker by name is registered.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasIssueTracker($name)
    {
        return isset($this->issueTrackers[$name]);
    }

    /**
     * Returns the registered issue trackers.
     *
     * @return array[]
     */
    public function getIssueTrackers()
    {
        return $this->issueTrackers;
    }

    /**
     * Creates a new Adapter object with the given configuration.
     *
     * @param string $name
     * @param array  $adapterConfig
     * @param Config $globalConfig
     *
     * @return Adapter
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function createAdapter($name, array $adapterConfig, Config $globalConfig)
    {
        if (!isset($this->adapters[$name])) {
            throw new \InvalidArgumentException(
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
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function createAdapterConfiguration($name, HelperSet $helperSet)
    {
        if (!isset($this->adapters[$name])) {
            throw new \InvalidArgumentException(
                sprintf('No Adapter with name "%s" is registered.', $name)
            );
        }

        $configurator = $this->adapters[$name][1]($helperSet);

        if (!$configurator instanceof Configurator) {
            throw new \LogicException(
                sprintf(
                    'Configurator-Factory callback returns an Gush\Adapter\Configurator instance, got "%s" instead.',
                    is_object($configurator) ? get_class($configurator) : gettype($configurator)
                )
            );
        }

        return $configurator;
    }

    /**
     * Creates a new IssueTracker object with the given configuration.
     *
     * @param string $name
     * @param array  $issueTrackerConfig
     * @param Config $globalConfig
     *
     * @return IssueTracker
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function createIssueTracker($name, array $issueTrackerConfig, Config $globalConfig)
    {
        if (!isset($this->issueTrackers[$name])) {
            throw new \InvalidArgumentException(
                sprintf('No IssueTracker with name "%s" is registered.', $name)
            );
        }

        $issueTracker = $this->issueTrackers[$name][0]($issueTrackerConfig, $globalConfig);

        if (!$issueTracker instanceof IssueTracker) {
            throw new \LogicException(
                sprintf(
                    'IssueTracker-Factory callback returns a Gush\Adapter\IssueTracker instance, got "%s" instead.',
                    is_object($issueTracker) ? get_class($issueTracker) : gettype($issueTracker)
                )
            );
        }

        return $issueTracker;
    }

    /**
     * Creates a new Configurator instance for the given issue tracker.
     *
     * @param string    $name      Name of the issue tracker (must be registered)
     * @param HelperSet $helperSet HelperSet object
     *
     * @return Configurator
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function createIssueTrackerConfiguration($name, HelperSet $helperSet)
    {
        if (!isset($this->issueTrackers[$name])) {
            throw new \InvalidArgumentException(
                sprintf('No issue tracker with name "%s" is registered.', $name)
            );
        }

        $configurator = $this->issueTrackers[$name][1]($helperSet);

        if (!$configurator instanceof Configurator) {
            throw new \LogicException(
                sprintf(
                    'Tracker configurator-Factory callback returns a Gush\Adapter\Configurator instance,'.
                    'got "%s" instead.',
                    is_object($configurator) ? get_class($configurator) : gettype($configurator)
                )
            );
        }

        return $configurator;
    }
}
