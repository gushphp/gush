<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Factory;

use Gush\Adapter\Adapter;
use Gush\Config;
use Gush\Factory\AdapterFactory;
use Gush\Tests\Fixtures\Adapter\TestAdapterFactory;
use Gush\Tests\Fixtures\Adapter\TestIssueTrackerFactory;
use Gush\Tests\Fixtures\Adapter\TestRepoManagerFactory;

class AdapterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdapterFactory
     */
    private $adapterFactory;

    /**
     * @var Config
     */
    private $config;

    protected function setUp()
    {
        $this->adapterFactory = new AdapterFactory();
        $this->config = new Config('/home/user', '/tmp');
    }

    public function testRegisterAdapterFactoryObject()
    {
        $repoManager = new TestAdapterFactory();

        $this->adapterFactory->register(
            'test',
            'Testing',
            $repoManager
        );

        $this->assertTrue($this->adapterFactory->has('test'));
        $this->assertFalse($this->adapterFactory->has('test2'));
    }

    public function testRegisterLazyAdapter()
    {
        $this->adapterFactory->register(
            'test',
            'Testing',
            'Gush\Tests\Fixtures\Adapter\TestIssueTrackerFactory'
        );

        $this->assertTrue($this->adapterFactory->has('test'));
        $this->assertFalse($this->adapterFactory->has('test2'));
    }

    public function testGetAllAdapters()
    {
        $this->assertEquals([], $this->adapterFactory->all());

        $repoManager = new TestRepoManagerFactory();

        $this->adapterFactory->register(
            'test',
            'Testing',
            $repoManager
        );

        $this->adapterFactory->register(
            'test2',
            'Testing2',
            'Gush\Tests\Fixtures\Adapter\TestIssueTrackerFactory'
        );

        $this->assertEquals(
            [
                'test' => [
                    'factory' => $repoManager,
                    'label' => 'Testing',
                    AdapterFactory::SUPPORT_REPOSITORY_MANAGER => true,
                    AdapterFactory::SUPPORT_ISSUE_TRACKER => false,
                ],
                'test2' => [
                    'factory' => 'Gush\Tests\Fixtures\Adapter\TestIssueTrackerFactory',
                    'label' => 'Testing2',
                    AdapterFactory::SUPPORT_REPOSITORY_MANAGER => false,
                    AdapterFactory::SUPPORT_ISSUE_TRACKER => true,
                ],
            ],
            $this->adapterFactory->all()
        );
    }

    public function testCannotRegisterAdapterWithExistingName()
    {
        $repoManager = new TestAdapterFactory();
        $issueTracker = new TestIssueTrackerFactory();

        $this->adapterFactory->register(
            'test',
            'Testing',
            $repoManager
        );

        $this->setExpectedException('InvalidArgumentException', 'An adapter with name "test" is already registered.');

        $this->adapterFactory->register(
            'test',
            'Testing2',
            $issueTracker
        );
    }

    public function testCreatesObjectFromLazyFactory()
    {
        $this->adapterFactory->register(
            'test',
            'Testing',
            'Gush\Tests\Fixtures\Adapter\TestAdapterFactory'
        );

        $createdAdapter = $this->adapterFactory->createRepositoryManager(
            'test',
            [],
            $this->config
        );

        $this->assertInstanceOf('Gush\Adapter\Adapter', $createdAdapter);
    }

    public function testCreateConfigurator()
    {
        $repoManager = new TestAdapterFactory();

        $this->adapterFactory->register(
            'test',
            'Testing',
            $repoManager
        );

        $createdConfigurator = $this->adapterFactory->createConfigurator(
            'test',
            $this->prophesize('Symfony\Component\Console\Helper\HelperSet')->reveal(),
            $this->config
        );

        $this->assertInstanceOf('Gush\Adapter\Configurator', $createdConfigurator);
    }

    public function testCreateRepositoryManagerAdapter()
    {
        $factory = new TestAdapterFactory();

        $this->adapterFactory->register(
            'test',
            'Testing',
            $factory
        );

        $createdAdapter = $this->adapterFactory->createRepositoryManager(
            'test',
            [],
            $this->config
        );

        $this->assertInstanceOf('Gush\Adapter\Adapter', $createdAdapter);
    }

    public function testCreateIssueTrackerAdapter()
    {
        $factory = new TestIssueTrackerFactory();

        $this->adapterFactory->register(
            'test',
            'Testing',
            $factory
        );

        $createdAdapter = $this->adapterFactory->createIssueTracker(
            'test',
            [],
            $this->config
        );

        $this->assertInstanceOf('Gush\Adapter\IssueTracker', $createdAdapter);
    }

    public function testCannotCreateUnregisteredAdapter()
    {
        $this->setExpectedException('InvalidArgumentException', 'No Adapter with name "test2" is registered.');

        $this->adapterFactory->createRepositoryManager(
            'test2',
            [],
            $this->config
        );
    }
}
