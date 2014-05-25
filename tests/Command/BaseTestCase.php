<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Adapter\DefaultConfigurator;
use Gush\Config;
use Gush\Event\CommandEvent;
use Gush\Event\GushEvents;
use Gush\Factory\AdapterFactory;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\TestableApplication;
use Guzzle\Http\Client;
use Prophecy\Prophet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Input\InputAwareInterface;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestAdapter
     */
    protected $adapter;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Prophet
     */
    protected $prophet;

    public function setUp()
    {
        $this->config = $this->getMock('Gush\Config');
        $this->adapter = $this->buildAdapter();
        $this->prophet = new Prophet();
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    /**
     * @param Command $command
     *
     * @return CommandTester
     */
    protected function getCommandTester(Command $command)
    {
        $adapterFactory = new AdapterFactory();
        $adapterFactory->registerAdapter(
            'github',
             function ($config) { return new TestAdapter($config); },
             function ($helperSet) { return new DefaultConfigurator($helperSet->get('dialog'), 'GitHub', 'https://api.github.com/', 'https://github.com'); }
        );

        $adapterFactory->registerAdapter(
            'github_enterprise',
             function ($config) { return new TestAdapter($config); },
             function ($helperSet) { return new DefaultConfigurator($helperSet->get('dialog'), 'GitHub Enterprise', '', ''); }
        );

        $application = new TestableApplication($adapterFactory);
        $application->setAutoExit(false);
        $application->setConfig($this->config);
        $application->setAdapter($this->adapter);
        $application->setVersionEyeClient($this->buildVersionEyeClient());

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

    /**
     * @return TestAdapter
     */
    protected function buildAdapter()
    {
        return new TestAdapter($this->config);
    }

    protected function buildVersionEyeClient()
    {
        $client = new Client();
        $client
            ->setBaseUrl('https://www.versioneye.com/')
            ->setDefaultOption('query', ['api_key' => '123'])
        ;

        return $client;
    }
}
