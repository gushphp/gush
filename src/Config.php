<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Config
{
    /**
     * @var array
     */
    public static $defaultConfig = [
        'cache-dir' => '{$home}/cache',
        'adapters' => [],
        'issue_trackers' => [],
    ];

    /**
     * @var array $config
     */
    private $config;

    private $errorList;

    public function __construct()
    {
        // load defaults
        $this->config = static::$defaultConfig;
        $this->errorList = [];
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
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        switch ($key) {
            case 'cache-dir':
                // convert foo-bar to GUSH_FOO_BAR and check if it exists since it overrides the local config
                $env = 'GUSH_'.strtoupper(strtr($key, '-', '_'));

                return rtrim(getenv($env) ?: $this->config[$key], '/\\');
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
        if (!$hasAdapter = count($this->config['adapters']) > 0) {
            $this->errorList[] = 'lacks adapters';
        }

        if (!$versioneyeTokenSet = isset($this->config['versioneye-token'])) {
            $this->errorList[] = 'versioneyeToken is not set';
        }

        if (!$cacheDirOk = (is_dir($this->get('cache-dir')) && is_writable($this->get('cache-dir')))) {
            $this->errorList[] = 'cache dir is not writable or does not exist';
        }

        return $hasAdapter && $cacheDirOk && $versioneyeTokenSet ;
    }

    /**
     * Return validation violations
     *
     * @return string[]
     */
    public function getErrorList()
    {
        return $this->errorList;
    }
}
