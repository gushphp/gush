<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Factory;
use Symfony\Component\Process\Process;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string $home
     */
    private $home;
    
    /**
     * @var string $cacheDir
     */
    private $cacheDir;

    public function testCreateConfigUnixEnv()
    {
        $this->home = getenv('GUSH_HOME');
        $this->cacheDir = getenv('GUSH_CACHE_DIR');

        if (!$this->home || !$this->cacheDir) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' and/OR \'GUSH_CACHE_DIR\' in your \'phpunit.xml\'.');
        }

        $home = $this->home;
        @mkdir($this->home, 0777, true);

        putenv("HOME=$this->home");

        $config = Factory::createConfig();

        $this->assertEquals($home.'/cache', $config->get('cache-dir'));
        $this->assertEquals($home, $config->get('home'));
        $this->assertFileExists($home.'/cache');
        $this->assertFileExists($home.'/cache/.htaccess');
        $this->assertFileExists($home.'/.htaccess');

        $process = new Process("rm -rf $this->home");
        $process->run();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateConfigWindowsEnv()
    {
        $this->home = getenv('GUSH_HOME');
        $this->cacheDir = getenv('GUSH_CACHE_DIR');

        if (!$this->home || !$this->cacheDir) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' and/OR \'GUSH_CACHE_DIR\' in your \'phpunit.xml\'.');
        }

        define('PHP_WINDOWS_VERSION_MAJOR', 1);
        $home = $this->home.'/Gush';

        @mkdir($this->home, 0777, true);

        putenv("HOME=$this->home");
        putenv("GUSH_HOME");
        putenv("GUSH_CACHE_DIR");
        putenv("APPDATA=$this->home");
        putenv("LOCALAPPDATA=$this->home");

        $config = Factory::createConfig();

        $this->assertEquals($home, $config->get('cache-dir'));
        $this->assertEquals($home, $config->get('home'));
        $this->assertFileExists($home.'/.htaccess');

        $process = new Process("rm -rf $this->home");
        $process->run();
    }
}
