<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Config;
use Gush\ConfigFactory;
use Symfony\Component\Yaml\Yaml;

/**
 * @group functional
 * @runTestsInSeparateProcesses
 */
class ConfigFactoryTest extends BaseTestCase
{
    private $homedir;

    protected function setUp()
    {
        parent::setUp();

        $this->homedir = $this->getNewTmpFolder('gush-home');

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
                'home' => $this->homedir,
                'home_config' => $this->homedir.'/.gush.yml',
            ],
            $config->toArray()
        );
    }

    public function testCreateConfigWithCustomCacheDir()
    {
        $cacheDir = $this->getNewTmpFolder('gush-cache');

        putenv('GUSH_CACHE_DIR='.$cacheDir);

        $config = ConfigFactory::createConfig();

        $this->assertFileExists($cacheDir.'/.htaccess');
        $this->assertFileExists($this->homedir.'/.htaccess');

        $this->assertEquals(
            [
                'adapters' => [],
                'home' => $this->homedir,
                'home_config' => $this->homedir.'/.gush.yml',
            ],
            $config->toArray()
        );
    }

    public function testCreateConfigWithExistingHomeConfig()
    {
        $content = <<<EOT
adapters:
    github:
        config: { base_url: 'https://api.github.com/', repo_domain_url: 'https://github.com' }
        authentication: { username: cordoval, password-or-token: password, http-auth-type: http_password }
EOT;

        file_put_contents($this->homedir.'/.gush.yml', $content);

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
                'home' => $this->homedir,
                'home_config' => $this->homedir.'/.gush.yml',
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
adapters:
    github:
        config: { base_url: 'https://api.github.com/', repo_domain_url: 'https://github.com' }
        authentication: { username: cordoval, password-or-token: password, http-auth-type: http_password }
EOT;

        file_put_contents($this->homedir.'/.gush.yml', $content);

        $localContent = <<<EOT
repo_adapter: bitbucket
EOT;

        $localDir = $this->getNewTmpFolder('gush-local');
        file_put_contents($localDir.'/.gush.yml', $localContent);

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
                'repo_adapter' => 'bitbucket',
                'home' => $this->homedir,
                'home_config' => $this->homedir.'/.gush.yml',
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
            ],
            $config->toArray(Config::CONFIG_SYSTEM)
        );

        $this->assertEquals(
            [
                'repo_adapter' => 'bitbucket',
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
            ],
            Yaml::parse(file_get_contents($this->homedir.'/.gush.yml'))
        );
    }

    public function testDumpConfigToLocalFile()
    {
        $localDir = $this->getNewTmpFolder('gush-local');

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
            ],
            Yaml::parse(file_get_contents($this->homedir.'/.gush.yml'))
        );
    }
}
