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

use Gush\Config;
use Gush\Event\CommandEvent;
use Gush\Event\GushEvents;
use Gush\Tests\TestableApplication;
use Guzzle\Http\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Input\InputAwareInterface;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestableApplication
     */
    protected $application;

    /**
     * @var Config
     */
    protected $config;

    public function setUp()
    {
        $application = new TestableApplication();
        $application->setAutoExit(false);
        $application->setVersionEyeClient($this->buildVersionEyeClient());
        $application->getDispatcher()->addListener(
            GushEvents::INITIALIZE,
            function ($event) {
                $command = $event->getCommand();
                $input   = $event->getInput();

                foreach ($command->getHelperSet() as $helper) {
                    if ($helper instanceof InputAwareInterface) {
                        $helper->setInput($input);
                    }
                }
            }
        );

        $this->application = $application;
        $this->config      = $application->getConfig();
    }

    /**
     * @param Command|string $command
     *
     * @return CommandTester
     */
    protected function getCommandTester($command)
    {
        if (!is_object($command)) {
            $command = new $command($this->application);
        } else {
            $command->setApplication($this->application);
        }

        $this->application->getDispatcher()->dispatch(
            GushEvents::DECORATE_DEFINITION,
            new CommandEvent($command)
        );

        return new CommandTester($command);
    }

    protected function buildVersionEyeClient()
    {
        $client = new Client();
        $client
            ->setBaseUrl('https://www-versioneye-com-'.getenv('RUNSCOPE_BUCKET').'.runscope.net/')
            ->setDefaultOption('query', ['api_key' => getenv('VERSIONEYE_TOKEN')])
        ;

        return $client;
    }
}
