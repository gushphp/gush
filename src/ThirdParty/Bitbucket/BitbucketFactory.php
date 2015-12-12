<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Bitbucket;

use Gush\Config;
use Gush\Factory\IssueTrackerFactory;
use Gush\Factory\RepositoryManagerFactory;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitbucketFactory implements IssueTrackerFactory, RepositoryManagerFactory
{
    /**
     * @var BitBucketClient|null
     */
    private static $client;

    public function createRepositoryManager(array $adapterConfig, Config $config)
    {
        return new BitbucketRepoAdapter($adapterConfig, static::getBitBucketClient($adapterConfig));
    }

    public function createConfigurator(HelperSet $helperSet, Config $config)
    {
        $configurator = new BitBucketConfigurator(
            $helperSet->get('question'),
            'Bitbucket',
            'https://bitbucket.org/api/',
            'https://bitbucket.org'
        );

        return $configurator;
    }

    public function createIssueTracker(array $adapterConfig, Config $config)
    {
        return new BitbucketIssueTracker($adapterConfig, static::getBitBucketClient($adapterConfig));
    }

    /**
     * @param array $options
     *
     * @return BitBucketClient
     */
    protected static function getBitBucketClient(array $options = [])
    {
        if (null === static::$client || static::$client->getOptions() !== $options) {
            static::$client = new BitBucketClient($options);
        }

        return static::$client;
    }
}
