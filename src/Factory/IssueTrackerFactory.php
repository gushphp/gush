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

use Gush\Adapter\Configurator;
use Gush\Adapter\IssueTracker;
use Gush\Config;
use Symfony\Component\Console\Helper\HelperSet;

interface IssueTrackerFactory
{
    /**
     * @param HelperSet $helperSet
     *
     * @return Configurator
     */
    public function createConfigurator(HelperSet $helperSet);

    /**
     * @param array  $adapterConfig
     * @param Config $config
     *
     * @return IssueTracker
     */
    public function createIssueTracker(array $adapterConfig, Config $config);
}
