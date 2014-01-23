<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Github\Client;
use Gush\Tester\HttpClient\TestHttpClient;
use Gush\Tests\TestableApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Gush\Event\CommandEvent;
use Gush\Event\GushEvents;

/**
 * @author Daniel T Leech <daniel@dantleech.com>
 */
class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestHttpClient
     */
    protected $httpClient;

    public function setUp()
    {
        $this->httpClient = new TestHttpClient();
    }

    /**
     * @param  Command       $command
     * @return CommandTester
     */
    protected function getCommandTester(Command $command)
    {
        $application = new TestableApplication();
        $application->setAutoExit(false);
        $application->setGithubClient($this->buildGithubClient());

        $command->setApplication($application);

        $application->getDispatcher()->dispatch(
            GushEvents::DECORATE_DEFINITION,
            new CommandEvent($command)
        );

        return new CommandTester($command);
    }

    protected function buildGithubClient()
    {
        return new Client($this->httpClient);
    }
}
