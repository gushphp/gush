<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Gitlab\Adapter;

use Gitlab\Client;
use Gush\Adapter\Configurator;
use Gush\Config;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
class GitLabFactory
{
    protected static $client;

    /**
     * Creates a new GitLabAdapter object with the given Configuration.
     *
     * @param array  $adapterConfig
     * @param Config $globalConfig
     *
     * @return GitLabAdapter
     */
    public static function createAdapter(array $adapterConfig, Config $globalConfig)
    {
        $adapter = new GitLabRepoAdapter($adapterConfig);

        return $adapter->setClient(static::getGitlabClient($adapterConfig['base_url']));
    }

    /**
     * Creates a new Configurator instance for the gitlab adapter.
     *
     * @param HelperSet $helperSet HelperSet object
     *
     * @return Configurator
     */
    public static function createAdapterConfigurator(HelperSet $helperSet)
    {
        return new GitlabConfigurator(
            $helperSet->get('question'),
            'Gitlab',
            'http://gitlab-host/api/v3',
            'http://gitlab-host',
            [['Token', Configurator::AUTH_HTTP_TOKEN]]
        );
    }

    /**
     * Creates a new GitLabAdapter object with the given Configuration.
     *
     * @param array  $trackerConfig
     * @param Config $globalConfig
     *
     * @return GitLabAdapter
     */
    public static function createIssueTracker(array $trackerConfig, Config $globalConfig)
    {
        $issueTracker = new GitLabIssueTracker($trackerConfig);

        return $issueTracker->setClient(static::getGitlabClient($trackerConfig['base_url']));
    }

    /**
     * Creates a new Configurator instance for the gitlab issue tracker.
     *
     * @param HelperSet $helperSet HelperSet object
     *
     * @return Configurator
     */
    public static function createIssueTrackerConfigurator(HelperSet $helperSet)
    {
        return static::createAdapterConfigurator($helperSet);
    }

    /**
     * @param string $url
     *
     * @return Client
     */
    protected static function getGitlabClient($url)
    {
        if (null === static::$client || static::$client->getBaseUrl() !== $url) {
            static::$client = new Client(trim($url, '/').'/');
        }

        return static::$client;
    }
}
