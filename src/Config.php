<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class Config
{
    /**
     * @var array
     */
    public static $defaultConfig = [
        'adapters' => []
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
     *
     * @return mixed
     */
    public function get($key)
    {
        switch ($key) {
            case 'home':
                return rtrim($this->config[$key], '/\\');
            default:
                $accessor = PropertyAccess::createPropertyAccessor();
                try {
                    return $accessor->getValue($this->config, $key);
                } catch (NoSuchPropertyException $e) {
                    if (!isset($this->config[$key])) {
                        return null;
                    }

                    return $this->config[$key];
                }
        }
    }

    /**
     * @return array
     */
    public function raw()
    {
        return $this->config;
    }

    /**
     * Checks whether a setting exists
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return null !== $this->get($key);
    }

    /**
     * Validates if the configuration
     *
     * @return bool
     */
    public function isValid()
    {
        if (count($this->config['adapters']) > 0 && isset($this->config['versioneye-token'])) {
            return true;
        }

        return false;
    }
}
