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

use Gush\Factory\AdapterFactory;
use Prophecy\Argument;

class AdapterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdapterFactory
     */
    private $adapterFactory;

    protected function setUp()
    {
        $this->adapterFactory = new AdapterFactory();
    }

    /**
     * @test
     */
    public function registers_adapters()
    {
        $repoManager = $this->prophesize('Gush\Factory\RepositoryManagerFactory')->reveal();

        // Test with class-name (lazy init)
        $issueTracker = get_class($this->prophesize('Gush\Factory\IssueTrackerFactory')->reveal());

        $this->adapterFactory->register(
            'test',
            'Testing',
            $repoManager
        );

        $this->adapterFactory->register(
            'test2',
            'Testing2',
            $issueTracker
        );

        $this->assertTrue($this->adapterFactory->has('test'));
        $this->assertTrue($this->adapterFactory->has('test2'));
        $this->assertFalse($this->adapterFactory->has('test3'));
    }

    /**
     * @test
     */
    public function gets_adapters()
    {
        $this->assertEquals([], $this->adapterFactory->all());

        $repoManager = $this->prophesize('Gush\Factory\RepositoryManagerFactory')->reveal();
        $issueTracker = $this->prophesize('Gush\Factory\IssueTrackerFactory')->reveal();

        $this->adapterFactory->register(
            'test',
            'Testing',
            $repoManager
        );

        $this->adapterFactory->register(
            'test2',
            'Testing2',
            $issueTracker
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
                    'factory' => $issueTracker,
                    'label' => 'Testing2',
                    AdapterFactory::SUPPORT_REPOSITORY_MANAGER => false,
                    AdapterFactory::SUPPORT_ISSUE_TRACKER => true,
                ]
            ],
            $this->adapterFactory->all()
        );
    }

    /**
     * @test
     */
    public function registers_adapter_with_same_name()
    {
        $repoManager = $this->prophesize('Gush\Factory\RepositoryManagerFactory');
        $issueTracker = $this->prophesize('Gush\Factory\IssueTrackerFactory');

        $this->adapterFactory->register(
            'test',
            'Testing',
            $repoManager->reveal()
        );

        $this->setExpectedException('InvalidArgumentException', 'An adapter with name "test" is already registered.');

        $this->adapterFactory->register(
            'test',
            'Testing2',
            $issueTracker->reveal()
        );
    }

    /**
     * @test
     */
    public function creates_configurator()
    {
        $configurator = $this->prophesize('Gush\Factory\Configurator')->reveal();

        $repoManager = $this->prophesize('Gush\Factory\RepositoryManagerFactory');
        $repoManager->createConfigurator(Argument::any())->willReturn($configurator);

        $this->adapterFactory->register(
            'test',
            'Testing',
            $repoManager->reveal()
        );

        $createdConfigurator = $this->adapterFactory->createConfigurator(
            'test',
            $this->prophesize('Symfony\Component\Console\Helper\HelperSet')->reveal()
        );

        $this->assertEquals($configurator, $createdConfigurator);
    }

    /**
     * @test
     */
    public function creates_repository_manager()
    {
        $adapter = $this->prophesize('Gush\Adapter\Adapter')->reveal();

        $factory = $this->prophesize('Gush\Factory\RepositoryManagerFactory');
        $factory->createRepositoryManager(Argument::any(), Argument::any())->willReturn($adapter);

        $this->adapterFactory->register(
            'test',
            'Testing',
            $factory->reveal()
        );

        $createdAdapter = $this->adapterFactory->createRepositoryManager(
            'test',
            [],
            $this->prophesize('Gush\Config')->reveal()
        );

        $this->assertEquals($adapter, $createdAdapter);
    }

    /**
     * @test
     */
    public function creates_issue_tracker()
    {
        $adapter = $this->prophesize('Gush\Adapter\IssueTracker')->reveal();

        $factory = $this->prophesize('Gush\Factory\IssueTrackerFactory');
        $factory->createIssueTracker(Argument::any(), Argument::any())->willReturn($adapter);

        $this->adapterFactory->register(
            'test',
            'Testing',
            $factory->reveal()
        );

        $createdAdapter = $this->adapterFactory->createIssueTracker(
            'test',
            [],
            $this->prophesize('Gush\Config')->reveal()
        );

        $this->assertEquals($adapter, $createdAdapter);
    }

    /**
     * @test
     */
    public function creates_adapter_with_non_existent_name()
    {
        $this->setExpectedException('InvalidArgumentException', 'No Adapter with name "test2" is registered.');

        $this->adapterFactory->createRepositoryManager(
            'test2',
            [],
            $this->prophesize('Gush\Config')->reveal()
        );
    }
}
