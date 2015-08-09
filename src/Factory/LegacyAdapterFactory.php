<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
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

final class LegacyAdapterFactory implements RepositoryManagerFactory, IssueTrackerFactory
{
    /**
     * @var string
     */
    private $className;

    /**
     * @param $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * @param HelperSet $helperSet
     *
     * @return Configurator
     */
    public function createConfigurator(HelperSet $helperSet)
    {
        $className = $this->className;

        return $className::createAdapterConfigurator($helperSet);
    }

    /**
     * @param array  $adapterConfig
     * @param Config $config
     *
     * @return IssueTracker
     */
    public function createIssueTracker(array $adapterConfig, Config $config)
    {
        $className = $this->className;

        return $className::createIssueTracker($adapterConfig, $config);
    }

    /**
     * @param array  $adapterConfig
     * @param Config $config
     *
     * @return Adapter
     */
    public function createRepositoryManager(array $adapterConfig, Config $config)
    {
        $className = $this->className;

        return $className::createAdapter($adapterConfig, $config);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
