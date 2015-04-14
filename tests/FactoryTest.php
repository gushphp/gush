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

use Gush\Factory;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * @group functional
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @runInSeparateProcess
     */
    public function creates_config_in_unix()
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

        $config = Factory::createConfig(false);

        $this->assertEquals($home.'/cache', $config->get('cache-dir'));
        $this->assertEquals($home, $config->get('home'));
        $this->assertFileExists($home.'/cache');
        $this->assertFileExists($home.'/cache/.htaccess');
        $this->assertFileExists($home.'/.htaccess');

        $process = new Process("rm -rf {$home}");
        $process->run();
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function create_config_in_windows()
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

    /**
     * @test
     * @runInSeparateProcess
     */
    public function load_config_with_no_local()
    {
        $home = getenv('GUSH_HOME');
        $localDir = getenv('GUSH_LOCAL');
        $cacheDir = getenv('GUSH_CACHE_DIR');

        if (!$home || !$cacheDir || !$localDir) {
            $this->markTestSkipped(
                'Please configure the "GUSH_HOME", "GUSH_LOCAL" and "GUSH_CACHE_DIR" in your "phpunit.xml".'
            );
        }

        @mkdir($home, 0777, true);
        @mkdir($localDir, 0777, true);

        $config = [
            'parameters' => [
                'cache-dir' => '{$home}/cache',
                'adapters' => ['github' => []],
                'issue_trackers' => ['github' => []],
                'versioneye-token' => 'NO-TOKEN',
            ]
        ];

        $localConfig = [
            'meta-header' => 'This file is part of Gush package.'
        ];

        file_put_contents($home.'/.gush.yml', Yaml::dump($config));
        file_put_contents($localDir.'/.gush.yml', Yaml::dump($localConfig));

        chdir($localDir);

        $config = Factory::createConfig(true);
        $this->assertEquals(['github' => []], $config->get('adapters'));
        $this->assertTrue($config->has('meta-header'));

        $config = Factory::createConfig(true, false);
        $this->assertEquals(['github' => []], $config->get('adapters'));
        $this->assertFalse($config->has('meta-header'));

        $process = new Process("rm -rf {$home}");
        $process->run();
    }
}
