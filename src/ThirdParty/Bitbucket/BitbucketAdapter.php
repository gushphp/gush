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

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
trait BitbucketAdapter
{
    /**
     * @var BitBucketClient|null
     */
    protected $client;

    /**
     * @var bool
     */
    protected $authenticated;

    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $domain;

    /**
     * @param array           $config
     * @param BitBucketClient $client
     */
    public function __construct(array $config, BitBucketClient $client = null)
    {
        $this->configuration = $config;
        $this->url = $config['base_url'];
        $this->domain = $config['repo_domain_url'];
        $this->client = $client ?: new BitBucketClient($config);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $credentials = $this->configuration['authentication'];

        $this->client->authenticate($credentials, $credentials['http-auth-type']);
        $this->client->disableErrorListener(false);
        $this->authenticated = $this->client->apiUser()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenGenerationUrl()
    {
        if (isset($this->configuration['authentication'])) {
            return sprintf(
                'https://bitbucket.org/account/user/%s/api',
                $this->configuration['authentication']['username']
            );
        }

        return null;
    }

    protected function prepareParameters(array $parameters)
    {
        foreach ($parameters as $k => $v) {
            if (null === $v) {
                unset($parameters[$k]);
            }
        }

        return $parameters;
    }
}
