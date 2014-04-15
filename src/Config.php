<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class Config
{
    public static $defaultConfig = [
        'cache-dir' => '{$home}/cache',
        'adapter_class' => '\\Gush\\Adapter\\GitHubAdapter'
    ];

    /**
     * @var array $config
     */
    private $config;

    public function __construct()
    {
        // load defaults
        $this->config = static::$defaultConfig;
    }

    /**
     * Merges new config values with the existing ones (overriding)
     *
     * @param array $config
     */
    public function merge(array $config)
    {
        // override defaults with given config
        foreach ($config as $key => $val) {
            $this->config[$key] = $val;
        }
    }

    /**
     * Returns a setting
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        switch ($key) {
            case 'cache-dir':
                // convert foo-bar to GUSH_FOO_BAR and check if it exists since it overrides the local config
                $env = 'GUSH_'.strtoupper(strtr($key, '-', '_'));

                return rtrim(getenv($env) ? : $this->config[$key], '/\\');

            case 'home':
                return rtrim($this->config[$key], '/\\');

            default:
                if (!isset($this->config[$key])) {
                    return null;
                }

                return $this->config[$key];
        }
    }

    public function raw()
    {
        return $this->config;
    }

    /**
     * Checks whether a setting exists
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Validates if the configuration
     *
     * @return bool
     */
    public function isValid()
    {
        if (isset($this->config['authentication']['username'])
            && isset($this->config['authentication']['password-or-token'])
            && isset($this->config['authentication']['http-auth-type'])
            && isset($this->config['versioneye-token'])
            && is_dir($this->get('cache-dir'))
            && is_writable($this->get('cache-dir'))
        ) {
            return true;
        }

        return false;
    }
}
