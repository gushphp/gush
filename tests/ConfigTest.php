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

class ConfigTest extends BaseTestCase
{
    public function testCreateEmptyConfigWithDefaults()
    {
        $config = $this->createConfig();

        $this->assertEquals(
            [
                'adapters' => [],
                'issue_trackers' => [],
                'home' => $this->rootFs->url().'/gush',
                'home_config' => $this->rootFs->url().'/gush/.gush.yml',
                'cache-dir' => $this->rootFs->url().'/gush/cache',
            ],
            $config->toArray(Config::CONFIG_ALL)
        );

        $this->assertEquals(
            [
                'adapters' => [],
                'issue_trackers' => [],
            ],
            $config->toArray(Config::CONFIG_SYSTEM)
        );

        $this->assertEquals([], $config->toArray(Config::CONFIG_LOCAL));
    }

    public function testCreateConfigWithLoaded()
    {
        $config = $this->createConfig(
            [
                'adapters' => [
                    'github' => ['base_url' => 'url'],
                ],
            ]
        );

        $this->assertEquals(
            [
                'adapters' => [
                    'github' => ['base_url' => 'url']
                ],
                'issue_trackers' => [],
                'home' => $this->rootFs->url().'/gush',
                'home_config' => $this->rootFs->url().'/gush/.gush.yml',
                'cache-dir' => $this->rootFs->url().'/gush/cache',
            ],
            $config->toArray(Config::CONFIG_ALL)
        );

        $this->assertEquals(
            [
                'adapters' => [
                    'github' => ['base_url' => 'url'],
                ],
                'issue_trackers' => [],
            ],
            $config->toArray(Config::CONFIG_SYSTEM)
        );

        $this->assertEquals([], $config->toArray(Config::CONFIG_LOCAL));
    }

    public function testCreateConfigWithLoadedAndLocal()
    {
        $config = $this->createConfig(
            [
                'adapters' => [
                    'github' => ['base_url' => 'url'],
                ],
            ],
            [
                'repo_adapter' => ['name' => 'github']
            ]
        );

        $this->assertEquals(
            [
                'adapters' => [
                    'github' => ['base_url' => 'url']
                ],
                'issue_trackers' => [],
                'home' => $this->rootFs->url().'/gush',
                'home_config' => $this->rootFs->url().'/gush/.gush.yml',
                'cache-dir' => $this->rootFs->url().'/gush/cache',
                'local' => $this->rootFs->url().'/local-gush',
                'local_config' => $this->rootFs->url().'/local-gush/.gush.yml',
                'repo_adapter' => ['name' => 'github'],
            ],
            $config->toArray(Config::CONFIG_ALL)
        );

        $this->assertEquals(
            [
                'adapters' => [
                    'github' => ['base_url' => 'url'],
                ],
                'issue_trackers' => [],
            ],
            $config->toArray(Config::CONFIG_SYSTEM)
        );

        $this->assertEquals(
            [
                'repo_adapter' => ['name' => 'github']
            ],
            $config->toArray(Config::CONFIG_LOCAL)
        );
    }

    public function testGetConfigValueByKey()
    {
        $config = $this->createConfig();

        $this->assertEquals($this->rootFs->url().'/gush', $config->get('home'));
        $this->assertEquals($this->rootFs->url().'/gush/.gush.yml', $config->get('home_config'));
        $this->assertNull($config->get('no-key'));

        $this->assertEquals([], $config->get('adapters', Config::CONFIG_ALL));
        $this->assertEquals([], $config->get('adapters', Config::CONFIG_SYSTEM));
        $this->assertNull($config->get('adapters', Config::CONFIG_LOCAL));
    }

    public function testGetConfigValueByPath()
    {
        $config = $this->createConfig(
            [
                'adapters' => [
                    'github' => ['base_url' => 'url'],
                ],
            ]
        );

        $this->assertEquals($this->rootFs->url().'/gush', $config->get('[home]'));
        $this->assertEquals(['base_url' => 'url'], $config->get('[adapters][github]'));
        $this->assertEquals('url', $config->get('[adapters][github][base_url]'));
        $this->assertNull($config->get('[no-key]'));

        $this->assertEquals(
            ['github' => ['base_url' => 'url']],
            $config->get('[adapters]', Config::CONFIG_ALL)
        );

        $this->assertEquals(
            ['github' => ['base_url' => 'url']],
            $config->get('[adapters]', Config::CONFIG_SYSTEM)
        );

        $this->assertNull($config->get('[adapters]', Config::CONFIG_LOCAL));
    }

    public function testCannotGetConfigForUnsupportedSlot()
    {
        $config = $this->createConfig();

        $this->setExpectedException(
            'InvalidArgumentException',
            'Config slot "something" is not valid'
        );

        $config->get('adapters', 'something');
    }

    public function testSetConfigValue()
    {
        $config = $this->createConfig();

        $this->assertNull($config->get('repo_adapter'));

        $config->set('repo_adapter', 'github', Config::CONFIG_SYSTEM);
        $config->set('issue_adapter', 'jira', Config::CONFIG_SYSTEM);
        $config->set('adapters', ['github' => ['base_url' => 'url']], Config::CONFIG_SYSTEM);

        $this->assertEquals('github', $config->get('repo_adapter'));
        $this->assertEquals('jira', $config->get('issue_adapter'));
        $this->assertEquals(['github' => ['base_url' => 'url']], $config->get('adapters'));
        $this->assertTrue($config->has('issue_adapter', Config::CONFIG_SYSTEM));

        $this->assertNull($config->get('issue_adapter', Config::CONFIG_LOCAL));
        $this->assertFalse($config->has('issue_adapter', Config::CONFIG_LOCAL));

        // Ensure original options are not lost
        $this->assertEquals([], $config->get('issue_trackers', Config::CONFIG_ALL));
        $this->assertEquals([], $config->get('issue_trackers', Config::CONFIG_SYSTEM));
    }

    public function testCannotSetInvalidKey()
    {
        $config = $this->createConfig();

        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid configuration, can not set nested configuration-key "[my_key]"'
        );

        $config->set('[my_key]', '/root', Config::CONFIG_SYSTEM);
    }

    public function testCannotSetConfigForAll()
    {
        $config = $this->createConfig();

        $this->setExpectedException(
            'InvalidArgumentException',
            'Config slot "'.Config::CONFIG_ALL.'" is not valid for setting "my_key"'
        );

        $config->set('my_key', '/root', Config::CONFIG_ALL);
    }

    /**
     * @dataProvider provideInvalidValues
     *
     * @param mixed $value
     */
    public function testCannotSetInvalidValue($value)
    {
        $config = $this->createConfig();

        $this->setExpectedException(
            'InvalidArgumentException',
            sprintf(
                'Configuration can only a be scalar or an array, "%s" type given instead for "%s".',
                gettype($value),
                'my_key'
            )
        );

        $config->set('my_key', $value, Config::CONFIG_SYSTEM);
    }

    public static function provideInvalidValues()
    {
        return [
            [new \stdClass()],
            [imagecreate(1, 1)],
        ];
    }

    public function testCannotSetProtectedConfigKey()
    {
        $config = $this->createConfig();

        $this->setExpectedException(
            'InvalidArgumentException',
            'Configuration key "home" is protected and can not be overwritten.'
        );

        $config->set('home', '/root', Config::CONFIG_SYSTEM);
    }

    public function testMergeConfigWithExisting()
    {
        $config = $this->createConfig();

        $config->merge(
            [
                'repo_adapter' => 'github',
                'issue_adapter' => 'jira',
            ],
            Config::CONFIG_SYSTEM
        );

        $this->assertEquals('github', $config->get('repo_adapter'));
        $this->assertEquals('jira', $config->get('issue_adapter'));
    }

    private function createConfig(array $config = [], array $localConfig = [])
    {
        $localHome = [] !== $localConfig ? $this->rootFs->url().'/local-gush' : null;

        return new Config(
            $this->rootFs->url().'/gush',
            $this->rootFs->url().'/gush/cache',
            $config,
            $localHome,
            $localConfig
        );
    }
}
