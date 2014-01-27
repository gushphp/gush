<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Github\Client;
use Github\Tests\HttpClient\HttpClientTest;
use Gush\Application;
use Gush\Tester\HttpClient\TestHttpClient;
use Gush\Config;

class TestableApplication extends Application
{
    /**
     * @var HttpClientTest
     */
    protected $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new TestHttpClient();
        $this->setGithubClient($this->buildGithubClient());
    }

    /**
     * {@inheritdoc}
     */
    protected function buildGithubClient()
    {
        return new Client(new TestHttpClient([], $this->client));
    }

    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
