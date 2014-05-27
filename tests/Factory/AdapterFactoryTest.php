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

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class AdapterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;

    public function testRegisterAdapter()
    {
        $this->adapterFactory->registerAdapter(
            'test',
            function () {},
            function () {}
        );

        $this->adapterFactory->registerAdapter(
            'test2',
            array($this, 'createAdapterCallback'),
            array($this, 'createAdapterCallback')
        );

        $this->assertTrue($this->adapterFactory->hasAdapter('test'));
        $this->assertTrue($this->adapterFactory->hasAdapter('test2'));
        $this->assertFalse($this->adapterFactory->hasAdapter('test3'));
    }

    public function testGetAdapters()
    {
        $this->assertEquals([], $this->adapterFactory->getAdapters());

        $adapter = function () {};
        $configurator = function () {};

        $this->adapterFactory->registerAdapter(
            'test',
            $adapter,
            $configurator
        );

        $this->assertEquals(['test' => [$adapter, $configurator]], $this->adapterFactory->getAdapters());
    }

    public function testRegisterAdapterWithExistentName()
    {
        $this->adapterFactory->registerAdapter(
            'test',
            function () {},
            function () {}
        );

        $this->setExpectedException('InvalidArgumentException', 'An adapter with name "test" is already registered.');

        $this->adapterFactory->registerAdapter(
            'test',
            function () {},
            function () {}
        );
    }

    public function testCreateAdapter()
    {
        $configurator = function () {};

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

    public function testCreateConfigurator()
    {
        $adapter = function () {};

        $helperSetMock = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')->disableOriginalConstructor()->getMock();
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

    public function testCreateAdapterWithInvalidReturn()
    {
        $configurator = function () {};
        $config = $this->getMockBuilder('Gush\Config')->disableOriginalConstructor()->getMock();

        $adapterFactory = function () {
            return new \stdClass();
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapterFactory,
            $configurator
        );

        $this->setExpectedException('LogicException', 'Adapter-Factory callback is expected to return a Gush\Adapter\Adapter instance, got "stdClass" instead.');
        $this->adapterFactory->createAdapter('test', [], $config);
    }

    public function testCreateConfiguratorWithInvalidReturn()
    {
        $adapter = function () {};
        $helperSetMock = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')->disableOriginalConstructor()->getMock();

        $configuratorFactory = function () {
            return new \stdClass();
        };

        $this->adapterFactory->registerAdapter(
            'test',
            $adapter,
            $configuratorFactory
        );

        $this->setExpectedException('LogicException', 'Configurator-Factory callback is expected to return a Gush\Adapter\Configurator instance, got "stdClass" instead.');
        $this->adapterFactory->createAdapterConfiguration('test', $helperSetMock);
    }

    public function testCreateAdapterWithNoneExistentName()
    {
        $configurator = function () {};
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

    public function testCreateConfiguratorWithNoneExistentName()
    {
        $adapter = function () {};
        $helperSetMock = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')->disableOriginalConstructor()->getMock();

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
