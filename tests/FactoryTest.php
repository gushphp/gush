<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Factory;
use Symfony\Component\Process\Process;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 * @author Luis Cordova <cordoval@gmail.com>
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testCreateConfigUnixEnv()
    {
        $home = getenv('GUSH_HOME');
        $cacheDir = getenv('GUSH_CACHE_DIR');

        if (!$home || !$cacheDir) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' and/OR \'GUSH_CACHE_DIR\' in your \'phpunit.xml\'.');
        }

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('This test only runs on POSIX systems.');
        }

        @mkdir($home, 0777, true);

        putenv("HOME={$home}");

        $config = Factory::createConfig();

        $this->assertEquals($home.'/cache', $config->get('cache-dir'));
        $this->assertEquals($home, $config->get('home'));
        $this->assertFileExists($home.'/cache');
        $this->assertFileExists($home.'/cache/.htaccess');
        $this->assertFileExists($home.'/.htaccess');

        $process = new Process("rm -rf {$home}");
        $process->run();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateConfigWindowsEnv()
    {
        $home = getenv('GUSH_HOME');
        $cacheDir = getenv('GUSH_CACHE_DIR');

        if (!$home || !$cacheDir) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' and/OR \'GUSH_CACHE_DIR\' in your \'phpunit.xml\'.');
        }

        if (!defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('This test only runs on Windows.');
        }

        @mkdir($home, 0777, true);

        putenv("HOME={$home}");
        putenv("GUSH_HOME");
        putenv("GUSH_CACHE_DIR");
        putenv("APPDATA={$home}");
        putenv("LOCALAPPDATA={$home}");

        $config = Factory::createConfig(false);

        $this->assertEquals($home.'/Gush', $config->get('cache-dir'));
        $this->assertEquals($home.'/Gush', $config->get('home'));
        $this->assertFileExists($home.'/.htaccess');

        $process = new Process("rm -rf {$home}");
        $process->run();
    }
}
