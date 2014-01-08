<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Github\Client;
use Gush\Application;
use Gush\Tester\HttpClient\TestHttpClient;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application $application
     */
    protected $application;
    /**
     * @var TestHttpClient $httpClient
     */
    protected $httpClient;

    public function setUp()
    {
        $this->application = new TestableApplication();
        $this->application->setAutoExit(false);
    }

    public function testApplicationFirstRun()
    {
        $applicationTester = new ApplicationTester($this->application);
        $applicationTester->run(['command' => 'list']);

        $this->assertRegExp('/Available commands/', $applicationTester->getDisplay());
    }
}

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

    /**
     * {@inheritdoc}
     */
    protected function readParameters()
    {
        $this->config = [
            'home' => sys_get_temp_dir(),
            'cache-dir' => sys_get_temp_dir(),
            'github' => [
                'username' => 'foo',
                'password' => 'bar'
            ]
        ];
    }
}
