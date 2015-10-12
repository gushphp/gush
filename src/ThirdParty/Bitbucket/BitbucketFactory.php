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

use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitbucketFactory
{
    /**
     * @var BitBucketClient|null
     */
    protected static $client;

    public static function createAdapter(array $adapterConfig)
    {
        return new BitbucketRepoAdapter($adapterConfig, static::getBitBucketClient($adapterConfig));
    }

    public static function createAdapterConfigurator(HelperSet $helperSet)
    {
        $configurator = new BitBucketConfigurator(
            $helperSet->get('question'),
            'Bitbucket',
            'https://bitbucket.org/api/',
            'https://bitbucket.org'
        );

        return $configurator;
    }

    public static function createIssueTracker(array $adapterConfig)
    {
        return new BitbucketIssueTracker($adapterConfig, static::getBitBucketClient($adapterConfig));
    }

    public static function createIssueTrackerConfigurator(HelperSet $helperSet)
    {
        return static::createAdapterConfigurator($helperSet);
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
