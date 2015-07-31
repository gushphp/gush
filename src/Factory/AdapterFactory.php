<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
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
 */
class AdapterFactory
{
    const SUPPORT_REPOSITORY_MANAGER = 'supports_repository_manager';
    const SUPPORT_ISSUE_TRACKER = 'supports_issue_tracker';

    /**
     * @var object|string[]
     */
    private $adapters = [];

    /**
     * @param string        $name
     * @param string        $label
     * @param object|string $adapterFactory
     */
    public function register($name, $label, $adapterFactory)
    {
        if (isset($this->adapters[$name])) {
            throw new \InvalidArgumentException(sprintf('An adapter with name "%s" is already registered.', $name));
        }

        $this->adapters[$name] = $this->guardFactoryClassImplementation($name, $label, $adapterFactory);
    }

    /**
     * Returns whether adapter by name is registered.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->adapters[$name]);
    }

    /**
     * Returns whether the adapter by name supports
     * the requirements.
     *
     * @param string $name
     * @param string $supports
     *
     * @return bool
     */
    public function supports($name, $supports)
    {
        if (!isset($this->adapters[$name])) {
            return false;
        }

        return $this->adapters[$name][$supports];
    }

    /**
     * Returns registered adapters.
     *
     * @return array[]
     */
    public function all()
    {
        return $this->adapters;
    }

    /**
     * Returns all registered adapters of a specific type.
     *
     * @param string $type AdapterFactory::SUPPORT_REPOSITORY_MANAGER or
     *                     AdapterFactorySUPPORT_ISSUE_TRACKER
     *
     * @return array[]
     */
    public function allOfType($type)
    {
        return array_filter(
            $this->adapters,
            function ($adapter) use ($type) {
                return $adapter[$type];
            }
        );
    }

    /**
     * Returns the requested adapter-factory configuration.
     *
     * @param string $name
     *
     * @return array[]
     */
    public function get($name)
    {
        if (!isset($this->adapters[$name])) {
            throw new \InvalidArgumentException(sprintf('No Adapter with name "%s" is registered.', $name));
        }

        return $this->adapters[$name];
    }

    /**
     * Creates a new RepositoryManager (Adapter object) with the given configuration.
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
    public function createRepositoryManager($name, array $adapterConfig, Config $globalConfig)
    {
        $factory = $this->getFactoryObject($name);

        if (!$factory instanceof RepositoryManagerFactory) {
            throw new \LogicException(sprintf('Adapter %s does not support repository-management.', $name));
        }

        return $factory->createRepositoryManager($adapterConfig, $globalConfig);
    }

    /**
     * Creates a new IssueTracker (IssueTracker object) with the given configuration.
     *
     * @param string $name
     * @param array  $adapterConfig
     * @param Config $globalConfig
     *
     * @return IssueTracker
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function createIssueTracker($name, array $adapterConfig, Config $globalConfig)
    {
        $factory = $this->getFactoryObject($name);

        if (!$factory instanceof IssueTrackerFactory) {
            throw new \LogicException(sprintf('Adapter %s does not support issue-tracking.', $name));
        }

        return $factory->createIssueTracker($adapterConfig, $globalConfig);
    }

    /**
     * Creates a new Configurator instance for the given adapter.
     *
     * @param string    $name      Name of the adapter (must be registered)
     * @param HelperSet $helperSet HelperSet object
     *
     * @return Configurator
     */
    public function createConfigurator($name, HelperSet $helperSet)
    {
        return $this->getFactoryObject($name)->createConfigurator($helperSet);
    }

    /**
     * @param string $name
     *
     * @return IssueTrackerFactory|RepositoryManagerFactory
     *
     * @throws \InvalidArgumentException
     */
    private function getFactoryObject($name)
    {
        $baseAdapter = $name;

        if (!isset($this->adapters[$baseAdapter])) {
            throw new \InvalidArgumentException(sprintf('No Adapter with name "%s" is registered.', $baseAdapter));
        }

        if (!is_object($this->adapters[$baseAdapter]['factory'])) {
            $factory = $this->adapters[$baseAdapter]['factory'];

            $this->adapters[$baseAdapter]['factory'] = new $factory();
        }

        return $this->adapters[$baseAdapter]['factory'];
    }

    private function guardFactoryClassImplementation($name, $label, $adapterFactory)
    {
        $adapterFactoryClass = is_object($adapterFactory) ? get_class($adapterFactory) : $adapterFactory;
        $classImplements = class_implements($adapterFactoryClass);

        $repositoryManager = in_array('Gush\Factory\RepositoryManagerFactory', $classImplements, true);
        $issueTracker = in_array('Gush\Factory\IssueTrackerFactory', $classImplements, true);

        if (!$repositoryManager && !$issueTracker) {
            throw new \InvalidArgumentException(
                sprintf(
                    'AdapterFactory class "%s" should implement "Gush\Factory\RepositoryManagerFactory" and/or '.
                    '"Gush\Factory\IssueTrackerFactory".',
                    $adapterFactoryClass
                )
            );
        }

        return [
            'name' => $name,
            'label' => $label,
            'factory' => $adapterFactory,
            self::SUPPORT_REPOSITORY_MANAGER => $repositoryManager,
            self::SUPPORT_ISSUE_TRACKER => $issueTracker,
        ];
    }

}
