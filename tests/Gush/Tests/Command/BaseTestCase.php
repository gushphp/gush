<?php

/*
 * This file is part of the Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Github\Client;
use Gush\Application;
use Gush\Test\HttpClient\TestHttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author Daniel T Leech <dantleech@gmail.com>
 */
class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    protected $httpClient;

    public function setUp()
    {
        $this->httpClient = new TestHttpClient();
    }

    protected function buildGithubClient()
    {
        return new Client($this->httpClient);
    }

    protected function getCommandTester(Command $command)
    {
        $application = new Application();
        $application->setGithubClient($this->buildGithubClient());
        $command->setApplication($application);

        return new CommandTester($command);
    }
}
