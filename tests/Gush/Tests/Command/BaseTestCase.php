<?php

/**
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
use Symfony\Component\Console\Input\InputAwareInterface;

/**
 * @author Daniel Leech <daniel@dantleech.com>
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
        $config = $this->getMock('Gush\Config');
        $application = new TestableApplication();
        $application->setAutoExit(false);
        $application->setGithubClient($this->buildGithubClient());
        $application->setVersionEyeClient($this->buildVersionEyeClient());
        $application->setConfig($config);

        $command->setApplication($application);

        $application->getDispatcher()->dispatch(
            GushEvents::DECORATE_DEFINITION,
            new CommandEvent($command)
        );

        $application->getDispatcher()->addListener(GushEvents::INITIALIZE, function ($event) {
            $command = $event->getCommand();
            $input = $event->getInput();

            foreach ($command->getHelperSet() as $helper) {
                if ($helper instanceof InputAwareInterface) {
                    $helper->setInput($input);
                }
            }
        });

        return new CommandTester($command);
    }

    protected function buildGithubClient()
    {
        return new Client($this->httpClient);
    }

    protected function buildVersionEyeClient()
    {
        $client = new \Guzzle\Http\Client();
        $client->setBaseUrl('https://www-versioneye-com-'.getenv('RUNSCOPE_BUCKET').'.runscope.net/');
        $client->setDefaultOption('query', ['api_key' => getenv('VERSIONEYE_TOKEN')]);

        return $client;
    }
}
