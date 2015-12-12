<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Gitlab;

use Gitlab\Client;
use Gush\Adapter\Configurator;
use Gush\Config;
use Gush\Factory\IssueTrackerFactory;
use Gush\Factory\RepositoryManagerFactory;
use Gush\ThirdParty\Gitlab\Adapter\GitLabIssueTracker;
use Gush\ThirdParty\Gitlab\Adapter\GitLabRepoAdapter;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
class GitLabFactory implements IssueTrackerFactory, RepositoryManagerFactory
{
    private static $client;

    public function createRepositoryManager(array $adapterConfig, Config $config)
    {
        $adapter = new GitLabRepoAdapter($adapterConfig);

        return $adapter->setClient(static::getGitLabClient($adapterConfig['base_url']));
    }

    public function createConfigurator(HelperSet $helperSet, Config $config)
    {
        return new GitlabConfigurator(
            $helperSet->get('question'),
            'GitLab',
            'http://gitlab-host/api/v3',
            'http://gitlab-host',
            [['Token', Configurator::AUTH_HTTP_TOKEN]]
        );
    }

    public function createIssueTracker(array $trackerConfig, Config $globalConfig)
    {
        $issueTracker = new GitLabIssueTracker($trackerConfig);

        return $issueTracker->setClient(static::getGitLabClient($trackerConfig['base_url']));
    }

    /**
     * @param string $url
     *
     * @return Client
     */
    protected static function getGitLabClient($url)
    {
        if (null === static::$client || static::$client->getBaseUrl() !== $url) {
            static::$client = new Client(trim($url, '/').'/');
        }

        return static::$client;
    }
}
