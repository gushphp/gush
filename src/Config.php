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

/**
 * The Config class holds and configuration for the Gush application.
 *
 * Configuration is stored per type: system and local, and is always
 * merged into the "all" type which is only used for getting and not
 * setting.
 *
 * Note that the Config class does not check if the provided folders
 * are valid or even exist. This class is only for holding configuration.
 */
final class Config
{
    const CONFIG_ALL = 'all';
    const CONFIG_SYSTEM = 'system';
    const CONFIG_LOCAL = 'local';

    /**
     * Default "system" configuration.
     *
     * @var array
     */
    private static $defaultConfig = [
        'adapters' => [],
    ];

    /**
     * Array of protected configuration keys.
     *
     * @var string[]
     */
    private static $protectedConfig = [
        'home',
        'home_config',
        'cache-dir',
        'local',
        'local_config',
    ];

    /**
     * Configuration tree.
     *
     * Configuration is stored per type (system or local) and always
     * merged back into all. The "all" type is used for getting only.
     *
     * Configuration stored in a type is an array with the actual
     * configuration. In practice a configuration for "system" is stored
     * like in the $config property:
     *
     * "system" => [
     *    "adapters" => ["github" => [...]]
     * ],
     * "all" => [
     *    "adapters" => ["github" => [...]]
     * ]
     *
     * @var array[]
     */
    private $config = [
        self::CONFIG_ALL => [],
        self::CONFIG_SYSTEM => [],
        self::CONFIG_LOCAL => [],
    ];

    /**
     * Constructor.
     *
     * @param string      $homedir
     * @param string      $cacheDir
     * @param array       $config
     * @param string|null $localHome
     * @param array       $localConfig
     */
    public function __construct($homedir, $cacheDir, array $config = [], $localHome = null, array $localConfig = [])
    {
        $this->config[self::CONFIG_SYSTEM] = array_merge(static::$defaultConfig, $config);
        $this->config[self::CONFIG_ALL] = $this->config[self::CONFIG_SYSTEM];
        $this->config[self::CONFIG_ALL]['home'] = $homedir;
        $this->config[self::CONFIG_ALL]['home_config'] = $homedir.'/.gush.yml';
        $this->config[self::CONFIG_ALL]['cache-dir'] = $cacheDir;

        if (null !== $localHome) {
            $this->config[self::CONFIG_ALL]['local'] = $localHome;
            $this->config[self::CONFIG_ALL]['local_config'] = $localHome.'/.gush.yml';
            $this->config[self::CONFIG_ALL] = array_merge($this->config[self::CONFIG_ALL], $localConfig);

            $this->config[self::CONFIG_LOCAL] = $localConfig;
        }
    }

    /**
     * Merges new config values with the existing ones (overriding).
     *
     * This can only store a single-key level like "adapters" but
     * not "[adapters][github]".
     *
     * @param string                      $key   Single level config key
     * @param string|int|float|bool|array $value Value to store
     * @param string                      $type  Either Config::CONFIG_SYSTEM
     *                                           or Config::CONFIG_LOCAL
     *
     * @return Config
     */
    public function set($key, $value, $type)
    {
        if ('[' === $key[0]) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid configuration, cannot set nested configuration-key "%s". '.
                    'Store the top config instead like: key => [sub_key => value].',
                    $key
                )
            );
        }

        if ($type !== self::CONFIG_LOCAL && $type !== self::CONFIG_SYSTEM) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Config slot "%s" is not valid for setting "%s", use either: '.
                    'Config::CONFIG_SYSTEM or Config::CONFIG_LOCAL. '.
                    'Note that configuration is always merged back to Config::CONFIG_ALL',
                    $type,
                    $key
                )
            );
        }

        if (!is_scalar($value) && !is_array($value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Configuration can only a be scalar or an array, "%s" type given instead for "%s".',
                    gettype($value),
                    $key
                )
            );
        }

        if (in_array($key, self::$protectedConfig, true)) {
            throw new \InvalidArgumentException(
                sprintf('Configuration key "%s" is protected and cannot be overwritten.', $key)
            );
        }

        $this->config[$type][$key] = $value;
        $this->config[self::CONFIG_ALL][$key] = $value;

        return $this;
    }

    /**
     * Merges new config values with the existing ones (overriding).
     *
     * @param array $config
     */
    public function merge(array $config, $type)
    {
        foreach ($config as $key => $val) {
            $this->set($key, $val, $type);
        }
    }

    /**
     * Returns a config value.
     *
     * @param string|string[]             $keys     Single level key like 'adapters' or array-path
     *                                              like ['adapters', 'github']
     * @param string                      $type     Either Config::CONFIG_SYSTEM Config::CONFIG_LOCAL
     *                                              or Config::CONFIG_ALL
     * @param string|int|float|bool|array $default  Default value to use when no config is found (null)
     *
     * @return string|int|float|bool|array
     */
    public function get($keys, $type = self::CONFIG_ALL, $default = null)
    {
        $this->guardConfigSlot($type);

        $keys = (array) $keys;

        if (count($keys) === 1) {
            return array_key_exists($keys[0], $this->config[$type]) ? $this->config[$type][$keys[0]] : $default;
        }

        $current = $this->config[$type];

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }

            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Returns the first none-null configuration value.
     *
     * @param string[] $keys                        Array of single level keys like "adapters" or property-path
     *                                              "[adapters][github]" to check
     * @param string                      $type     Either Config::CONFIG_SYSTEM Config::CONFIG_LOCAL
     *                                              or Config::CONFIG_ALL
     * @param string|int|float|bool|array $default  Default value to use when no config is found (null)
     *
     * @return string|int|float|bool|array
     */
    public function getFirstNotNull(array $keys, $type = self::CONFIG_ALL, $default = null)
    {
        foreach ($keys as $key) {
            $value = $this->get($key, $type);

            if (null !== $value) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get the configuration is as array.
     *
     * @param string $type Either Config::CONFIG_SYSTEM Config::CONFIG_LOCAL
     *                     or Config::CONFIG_ALL
     *
     * @return array
     */
    public function toArray($type = self::CONFIG_ALL)
    {
        $this->guardConfigSlot($type);

        return $this->config[$type];
    }

    /**
     * Checks whether the config exists.
     *
     * Note. A value with null is considered undefined.
     *
     * @param string $key  Single level key like "adapters" or property-path
     *                     "[adapters][github]"
     * @param string $type Either Config::CONFIG_SYSTEM Config::CONFIG_LOCAL
     *                     or Config::CONFIG_ALL
     *
     * @return bool
     */
    public function has($key, $type = self::CONFIG_ALL)
    {
        return null !== $this->get($key, $type);
    }

    /**
     * Guard the configuration slot is valid.
     *
     * @param string $type
     */
    private function guardConfigSlot($type)
    {
        if (!isset($this->config[$type])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Config slot "%s" is not valid, use either: '.
                    'Config::CONFIG_ALL, Config::CONFIG_SYSTEM or Config::CONFIG_LOCAL.',
                    $type
                )
            );
        }
    }
}
