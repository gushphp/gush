<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\tests;

use Github\Client;
use Gush\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function configures()
    {
        $defaultConfig = Config::$defaultConfig;
        $config = new Config();

        $this->assertEquals($defaultConfig, $config->raw());

        $config->merge(['foo' => 'bar']);

        $defaultConfig['foo'] = 'bar';
        $this->assertEquals($defaultConfig, $config->raw());
        $this->assertEquals('bar', $config->get('foo'));
        $this->assertTrue($config->has('foo'));

        $this->assertNull($config->get('foobar'));

        $this->assertFalse($config->isValid());
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function configure_with_valid_configuration()
    {
        // clean Environment variables to avoid creating the folders
        putenv('GUSH_HOME');
        putenv('GUSH_CACHE_DIR');

        $config = new Config();
        $config->merge(
            [
                'adapters' => [
                    'github' => [
                        'adapter_class' => '\\Gush\\Tester\\Adapter\\TestAdapter',
                        'authentication' => [
                            'username' => 'foo',
                            'password-or-token' => 'bar',
                            'http-auth-type' => Client::AUTH_HTTP_PASSWORD,
                        ],
                    ]
                ],
                'cache-dir' => sys_get_temp_dir(),
                'versioneye-token' => '1234',
            ]
        );

        $this->assertTrue($config->isValid());
    }
}
