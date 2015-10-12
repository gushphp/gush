<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Jira;

use Gush\Adapter\DefaultConfigurator;
use Gush\Config;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class JiraFactory
{
    /**
     * @param array  $adapterConfig
     * @param Config $config
     *
     * @return JiraIssueTracker
     */
    public static function createIssueTracker(array $adapterConfig, Config $config)
    {
        return new JiraIssueTracker($adapterConfig, $config);
    }

    /**
     * @param HelperSet $helperSet
     *
     * @return DefaultConfigurator
     */
    public static function createIssueTrackerConfigurator(HelperSet $helperSet)
    {
        return new DefaultConfigurator(
            $helperSet->get('question'),
            'Jira issue tracker',
            'http://jira.atlassian.com/rest/api/2/',
            'http://jira.atlassian.com/',
            [['Password', DefaultConfigurator::AUTH_HTTP_PASSWORD]]
        );
    }
}
