<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Jira;

use Gush\Adapter\DefaultConfigurator;
use Gush\Config;
use Gush\Factory\IssueTrackerFactory;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class JiraFactory implements IssueTrackerFactory
{
    public function createIssueTracker(array $adapterConfig, Config $config)
    {
        return new JiraIssueTracker($adapterConfig, $config);
    }

    public function createConfigurator(HelperSet $helperSet, Config $config)
    {
        return new DefaultConfigurator(
            $helperSet->get('question'),
            'Jira issue tracker',
            'https://jira.atlassian.com/rest/api/2/',
            'https://jira.atlassian.com/',
            [['Password', DefaultConfigurator::AUTH_HTTP_PASSWORD]]
        );
    }
}
