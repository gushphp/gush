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
 * @author Luis Cordova <cordoval@gmail.com>
 */
class JiraEnterpriseFactory
{
    /**
     * @param array $adapterConfig
     * @param Config $config
     *
     * @return JiraEnterpriseIssueTracker
     */
    public static function createIssueTracker(array $adapterConfig, Config $config)
    {
        return new JiraEnterpriseIssueTracker($adapterConfig, $config);
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
            'Jira Enterprise issue tracker',
            'http://jira.domain.net:8081/rest/api/2/',
            'http://jira.domain.net:8081/'
        );
    }
}
