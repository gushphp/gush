<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Factory;

use Gush\Adapter\Adapter;
use Gush\Adapter\Configurator;
use Gush\Adapter\IssueTracker;
use Gush\Config;
use Gush\Factory\AdapterFactory;
use Gush\Tests\Fixtures\Adapter\TestAdapterFactory;
use Gush\Tests\Fixtures\Adapter\TestIssueTrackerFactory;
use Gush\Tests\Fixtures\Adapter\TestRepoManagerFactory;
use Symfony\Component\Console\Helper\HelperSet;

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
            TestIssueTrackerFactory::class
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
            TestIssueTrackerFactory::class
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
                    'factory' => TestIssueTrackerFactory::class,
                    'label' => 'Testing2',
                    AdapterFactory::SUPPORT_REPOSITORY_MANAGER => false,
                    AdapterFactory::SUPPORT_ISSUE_TRACKER => true,
                ]
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
            TestAdapterFactory::class
        );

        $createdAdapter = $this->adapterFactory->createRepositoryManager(
            'test',
            [],
            $this->config
        );

        $this->assertInstanceOf(Adapter::class, $createdAdapter);
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
            $this->prophesize(HelperSet::class)->reveal()
        );

        $this->assertInstanceOf(Configurator::class, $createdConfigurator);
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

        $this->assertInstanceOf(Adapter::class, $createdAdapter);
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

        $this->assertInstanceOf(IssueTracker::class, $createdAdapter);
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
