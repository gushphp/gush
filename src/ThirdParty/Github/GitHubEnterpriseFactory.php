<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Gush\Config;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GitHubEnterpriseFactory
{
    public static function createAdapter($adapterConfig, Config $config)
    {
        return new GitHubEnterpriseAdapter($adapterConfig, $config);
    }

    public static function createAdapterConfigurator(HelperSet $helperSet)
    {
        $configurator = new DefaultConfigurator(
            $helperSet->get('question'),
            'GitHub Enterprise',
            'https://api.github.com/',
            'https://github.com'
        );

        return $configurator;
    }

    public static function createIssueTracker($adapterConfig, Config $config)
    {
        return new GitHubEnterpriseAdapter($adapterConfig, $config);
    }

    public static function createIssueTrackerConfigurator(HelperSet $helperSet)
    {
        $configurator = new DefaultConfigurator(
            $helperSet->get('question'),
            'GitHub Enterprise Issue Tracker',
            '',
            ''
        );

        return $configurator;
    }
}
