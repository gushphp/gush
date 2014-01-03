<?php

/*
 * This file is part of the Gush.
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
    /** @var string $home */
    private $home = '/tmp/gush';

    /**
     * @dataProvider provider
     * @runInSeparateProcess
     */
    public function testCreateConfigUnixEnv($env, $dir)
    {
        $home = $this->home.'/.gush';

        @mkdir($this->home, 0777, true);

        putenv("HOME=$this->home");
        putenv("$env=$dir");

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
        define('PHP_WINDOWS_VERSION_MAJOR', 1);
        $home = $this->home.'/Gush';

        @mkdir($this->home, 0777, true);

        putenv("HOME=$this->home");
        putenv("APPDATA=$this->home");
        putenv("LOCALAPPDATA=$this->home");

        $config = Factory::createConfig();

        $this->assertEquals($home, $config->get('cache-dir'));
        $this->assertEquals($home, $config->get('home'));
        $this->assertFileExists($home.'/.htaccess');

        $process = new Process("rm -rf $this->home");
        $process->run();
    }

    public function testCreateAdditionalStyles()
    {
        $styles = Factory::createAdditionalStyles();

        $this->assertArrayHasKey('highlight', $styles);
        $this->assertArrayHasKey('warning', $styles);
    }

    public function provider()
    {
        return array(
            array('GUSH_HOME', "$this->home/.gush"),
            array('GUSH_CACHE_DIR', "$this->home/.gush/cache")
        );
    }
}
