<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Factory;

use Gush\Factory\AdapterFactory;

class AdapterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;

    /**
     * @test
     */
    public function registers_adapters()
    {
        $this->adapterFactory->registerAdapter(
            'test',
            function () {
                // no op
            },
            function () {
                // no op
            }
        );

        $this->adapterFactory->registerAdapter(
            'test2',
            [$this, 'createAdapterCallback'],
            [$this, 'createAdapterCallback']
        );

        $this->assertTrue($this->adapterFactory->hasAdapter('test'));
        $this->assertTrue($this->adapterFactory->hasAdapter('test2'));
        $this->assertFalse($this->adapterFactory->hasAdapter('test3'));
    }

    /**
     * @test
     */
    public function gets_adapters()
    {
        $this->assertEquals([], $this->adapterFactory->getAdapters());

        $adapter = function () {
            // no op
        };
        $configurator = function () {
            // no op
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapter,
            $configurator
        );

        $this->assertEquals(['test' => [$adapter, $configurator]], $this->adapterFactory->getAdapters());
    }

    /**
     * @test
     */
    public function registers_adapter_with_same_name()
    {
        $this->adapterFactory->registerAdapter(
            'test',
            function () {
                // no op
            },
            function () {
                // no op
            }
        );

        $this->setExpectedException('InvalidArgumentException', 'An adapter with name "test" is already registered.');

        $this->adapterFactory->registerAdapter(
            'test',
            function () {
                // no op
            },
            function () {
                // no op
            }
        );
    }

    /**
     * @test
     */
    public function creates_adapter()
    {
        $configurator = function () {
            // no op
        };

        $adapterMock = $this->getMock('Gush\Adapter\Adapter');
        $config = $this->getMockBuilder('Gush\Config')->disableOriginalConstructor()->getMock();

        $adapterFactory = function ($adapterConfig, $globalConfig) use ($config, $adapterMock) {
            $this->assertEquals(
                [
                    'authorization' => [
                        'username' => 'user',
                        'password' => 'password'
                    ]
                ],
                $adapterConfig
            );

            $this->assertEquals($config, $globalConfig);

            return $adapterMock;
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapterFactory,
            $configurator
        );

        $adapter = $this->adapterFactory->createAdapter(
            'test',
            [
                'authorization' => [
                    'username' => 'user',
                    'password' => 'password'
                ]
            ],
            $config
        );

        $this->assertEquals($adapterMock, $adapter);
    }

    /**
     * @test
     */
    public function creates_configurator()
    {
        $adapter = function () {
            // no op
        };

        $helperSetMock = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $configuratorMock = $this->getMock('Gush\Adapter\Configurator');

        $configuratorFactory = function ($helperSet) use ($helperSetMock, $configuratorMock) {
            $this->assertSame($helperSetMock, $helperSet);

            return $configuratorMock;
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapter,
            $configuratorFactory
        );

        $configurator = $this->adapterFactory->createAdapterConfiguration('test', $helperSetMock);
        $this->assertEquals($configuratorMock, $configurator);
    }

    /**
     * @test
     */
    public function creates_adapter_with_invalid_return()
    {
        $configurator = function () {
            // no op
        };
        $config = $this->getMockBuilder('Gush\Config')->disableOriginalConstructor()->getMock();

        $adapterFactory = function () {
            return new \stdClass();
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapterFactory,
            $configurator
        );

        $this->setExpectedException(
            'LogicException',
            'Adapter-Factory callback should return a Gush\Adapter\Adapter instance, got "stdClass"'
        );

        $this->adapterFactory->createAdapter('test', [], $config);
    }

    /**
     * @test
     */
    public function creates_configurator_with_invalid_return()
    {
        $adapter = function () {
            // no op
        };
        $helperSetMock = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $configuratorFactory = function () {
            return new \stdClass();
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapter,
            $configuratorFactory
        );

        $this->setExpectedException(
            'LogicException',
            'Configurator-Factory callback should return a Gush\Adapter\Configurator instance, got "stdClass"'
        );

        $this->adapterFactory->createAdapterConfiguration('test', $helperSetMock);
    }

    /**
     * @test
     */
    public function creates_adapter_with_non_existent_name()
    {
        $configurator = function () {
            // no op
        };
        $config = $this->getMockBuilder('Gush\Config')->disableOriginalConstructor()->getMock();

        $adapterFactory = function () {
            return new \stdClass();
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapterFactory,
            $configurator
        );

        $this->setExpectedException('InvalidArgumentException', 'No Adapter with name "test2" is registered.');
        $this->adapterFactory->createAdapter('test2', [], $config);
    }

    /**
     * @test
     */
    public function creates_configurator_with_non_existing_name()
    {
        $adapter = function () {
            // no op
        };
        $helperSetMock = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $configuratorFactory = function () {
            return new \stdClass();
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapter,
            $configuratorFactory
        );

        $this->setExpectedException('InvalidArgumentException', 'No Adapter with name "test2" is registered.');
        $this->adapterFactory->createAdapterConfiguration('test2', $helperSetMock);
    }

    protected function setUp()
    {
        $this->adapterFactory = new AdapterFactory();
    }

    public function createAdapterCallback()
    {
    }
}
