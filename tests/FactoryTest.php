<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Config;
use Gush\ConfigFactory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @group functional
 * @runTestsInSeparateProcesses
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    private $homedir;

    protected function setUp()
    {
        putenv('GUSH_CACHE_DIR');

        // Cant use a virtual filesystem here because PHP does not
        // support to rename file:///temp/file to vfs://gush-home.
        $homedir = sys_get_temp_dir();

        if (!$homedir) {
            $this->markTestSkipped('No system temp folder configured.');
        }

        $this->homedir = $homedir.'/gush-home-'.microtime(true);

        $this->assertFileNotExists($this->homedir);

        putenv('GUSH_HOME='.$this->homedir);
        putenv('GUSH_CACHE_DIR');
    }

    public function testCreateConfigWithGushHomeNotExisting()
    {
        $config = ConfigFactory::createConfig();

        $this->assertFileExists($this->homedir.'/cache/.htaccess');
        $this->assertFileExists($this->homedir.'/.htaccess');

        $this->assertEquals(
            [
                'adapters' => [],
                'issue_trackers' => [],
                'home' => $this->homedir,
                'home_config' => $this->homedir.'/.gush.yml',
                'cache-dir' => $this->homedir.'/cache',
            ],
            $config->toArray()
        );
    }

    public function testCreateConfigWithCustomCacheDir()
    {
        $cacheDir = sys_get_temp_dir().'/gush-cache-'.microtime(true);

        putenv('GUSH_CACHE_DIR='.$cacheDir);

        $config = ConfigFactory::createConfig();

        $this->assertFileExists($cacheDir.'/.htaccess');
        $this->assertFileExists($this->homedir.'/.htaccess');

        $this->assertEquals(
            [
                'adapters' => [],
                'issue_trackers' => [],
                'home' => $this->homedir,
                'home_config' => $this->homedir.'/.gush.yml',
                'cache-dir' => $cacheDir,
            ],
            $config->toArray()
        );
    }

    public function testCreateConfigWithExistingHomeConfig()
    {
        $content = <<<EOT
adapter: github
adapters:
    github:
        config: { base_url: 'https://api.github.com/', repo_domain_url: 'https://github.com' }
        authentication: { username: cordoval, password-or-token: password, http-auth-type: http_password }
EOT;

        (new Filesystem())->dumpFile($this->homedir.'/.gush.yml', $content);

        $config = ConfigFactory::createConfig();

        $this->assertEquals(
            [
                'adapters' => [
                    'github' => [
                        'config' => [
                            'base_url' => 'https://api.github.com/',
                            'repo_domain_url' => 'https://github.com',
                        ],
                        'authentication' => [
                            'username' => 'cordoval',
                            'password-or-token' => 'password',
                            'http-auth-type' => 'http_password',
                        ],
                    ],
                ],
                'issue_trackers' => [],
                'adapter' => 'github',
                'home' => $this->homedir,
                'home_config' => $this->homedir.'/.gush.yml',
                'cache-dir' => $this->homedir.'/cache',
            ],
            $config->toArray(Config::CONFIG_ALL)
        );

        $this->assertEquals(
            [
                'adapters' => [
                    'github' => [
                        'config' => [
                            'base_url' => 'https://api.github.com/',
                            'repo_domain_url' => 'https://github.com',
                        ],
                        'authentication' => [
                            'username' => 'cordoval',
                            'password-or-token' => 'password',
                            'http-auth-type' => 'http_password',
                        ],
                    ],
                ],
                'issue_trackers' => [],
                'adapter' => 'github',
            ],
            $config->toArray(Config::CONFIG_SYSTEM)
        );

        $this->assertEquals(
            [],
            $config->toArray(Config::CONFIG_LOCAL)
        );
    }

    public function testCreateConfigWithExistingHomeAndLocalConfig()
    {
        $content = <<<EOT
adapter: github
adapters:
    github:
        config: { base_url: 'https://api.github.com/', repo_domain_url: 'https://github.com' }
        authentication: { username: cordoval, password-or-token: password, http-auth-type: http_password }
EOT;

        (new Filesystem())->dumpFile($this->homedir.'/.gush.yml', $content);

        $localContent = <<<EOT
adapter: bitbucket
EOT;

        $localDir = sys_get_temp_dir().'/gush-local-'.microtime(true);
        $this->assertFileNotExists($localDir);
        (new Filesystem())->dumpFile($localDir.'/.gush.yml', $localContent);

        $config = ConfigFactory::createConfig($localDir);

        $this->assertEquals(
            [
                'adapters' => [
                    'github' => [
                        'config' => [
                            'base_url' => 'https://api.github.com/',
                            'repo_domain_url' => 'https://github.com',
                        ],
                        'authentication' => [
                            'username' => 'cordoval',
                            'password-or-token' => 'password',
                            'http-auth-type' => 'http_password',
                        ],
                    ],
                ],
                'issue_trackers' => [],
                'adapter' => 'bitbucket',
                'home' => $this->homedir,
                'home_config' => $this->homedir.'/.gush.yml',
                'cache-dir' => $this->homedir.'/cache',
                'local' => $localDir,
                'local_config' => $localDir.'/.gush.yml',
            ],
            $config->toArray(Config::CONFIG_ALL)
        );

        $this->assertEquals(
            [
                'adapters' => [
                    'github' => [
                        'config' => [
                            'base_url' => 'https://api.github.com/',
                            'repo_domain_url' => 'https://github.com',
                        ],
                        'authentication' => [
                            'username' => 'cordoval',
                            'password-or-token' => 'password',
                            'http-auth-type' => 'http_password',
                        ],
                    ],
                ],
                'issue_trackers' => [],
                'adapter' => 'github',
            ],
            $config->toArray(Config::CONFIG_SYSTEM)
        );

        $this->assertEquals(
            [
                'adapter' => 'bitbucket',
            ],
            $config->toArray(Config::CONFIG_LOCAL)
        );
    }

    public function testDumpConfigToSystemFile()
    {
        $config = ConfigFactory::createConfig();

        $this->assertFileExists($this->homedir.'/cache/.htaccess');
        $this->assertFileExists($this->homedir.'/.htaccess');
        $this->assertFileNotExists($this->homedir.'/.gush.yml');

        ConfigFactory::dumpToFile($config, Config::CONFIG_SYSTEM);

        $this->assertFileExists($this->homedir.'/.gush.yml');
        $this->assertEquals(
            [
                'adapters' => [],
                'issue_trackers' => [],
            ],
            Yaml::parse(file_get_contents($this->homedir.'/.gush.yml'))
        );
    }

    public function testDumpConfigToLocalFile()
    {
        $localDir = sys_get_temp_dir().'/gush-local-'.microtime(true);

        $config = ConfigFactory::createConfig($localDir);
        $config->merge(
            [
                'adapter' => 'bitbucket',
            ],
            Config::CONFIG_LOCAL
        );

        $this->assertFileNotExists($localDir.'/.gush.yml');

        ConfigFactory::dumpToFile($config, Config::CONFIG_LOCAL);
        ConfigFactory::dumpToFile($config, Config::CONFIG_SYSTEM);

        $this->assertFileExists($localDir.'/.gush.yml');
        $this->assertEquals(
            ['adapter' => 'bitbucket'],
            Yaml::parse(file_get_contents($localDir.'/.gush.yml'))
        );

        ConfigFactory::dumpToFile($config, Config::CONFIG_SYSTEM);

        $this->assertFileExists($this->homedir.'/.gush.yml');
        $this->assertEquals(
            [
                'adapters' => [],
                'issue_trackers' => [],
            ],
            Yaml::parse(file_get_contents($this->homedir.'/.gush.yml'))
        );
    }
}
