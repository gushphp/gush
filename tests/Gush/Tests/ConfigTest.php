<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Config;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $defaultConfig = Config::$defaultConfig;
        $config = new Config();

        $this->assertEquals($defaultConfig, $config->raw());

        $config->merge(array('foo' => 'bar'));

        $defaultConfig['foo'] = 'bar';
        $this->assertEquals($defaultConfig, $config->raw());
        $this->assertEquals('bar', $config->get('foo'));
        $this->assertTrue($config->has('foo'));

        $this->assertNull($config->get('foobar'));

        $this->assertFalse($config->isValid());
    }

    /**
     * @runInSeparateProcess
     */
    public function testConfigWithValidConfiguration()
    {
        // clean Environment variables to avoid creating the folders
        putenv('GUSH_HOME');
        putenv('GUSH_CACHE_DIR');

        $config = new Config();
        $config->merge(
            [
                'github' => [
                    'username' => 'foo',
                    'password' => 'bar'
                ],
                'cache-dir' => sys_get_temp_dir()
            ]
        );

        $this->assertTrue($config->isValid());
    }
}
