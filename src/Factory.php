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

use Gush\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates the default directory structure to run Gush
 */
class Factory
{
    /**
     * @return string
     */
    public static function getHomedir()
    {
        $home = getenv('GUSH_HOME');

        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if (!getenv('APPDATA')) {
                    throw new \RuntimeException(
                        'The APPDATA or GUSH_HOME environment variable must be set for Gush to run correctly'
                    );
                }

                $home = strtr(getenv('APPDATA'), '\\', '/').'/Gush';
            } else {
                if (!getenv('HOME')) {
                    throw new \RuntimeException(
                        'The HOME or GUSH_HOME environment variable must be set for Gush to run correctly'
                    );
                }

                $home = rtrim(getenv('HOME'), '/').'/.gush';
            }
        }

        return $home;
    }

    /**
     * @param bool $loadParameters If false, will return an empty config object
     * @param bool $loadLocal      If false this will skip the local config-file.
     *                             This option is only used when $loadParameters is true
     *
     * @return Config
     *
     * @throws \RuntimeException
     */
    public static function createConfig($loadParameters = true, $loadLocal = true)
    {
        // determine home and cache dirs
        $home = static::getHomedir();
        $cacheDir = getenv('GUSH_CACHE_DIR');

        if (!$cacheDir) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if ($cacheDir = getenv('LOCALAPPDATA')) {
                    $cacheDir .= '/Gush';
                } else {
                    $cacheDir = $home.'/cache';
                }
                $cacheDir = strtr($cacheDir, '\\', '/');
            } else {
                $cacheDir = $home.'/cache';
            }
        }

        // Protect directory against web access. Since HOME could be
        // the www-data's user home and be web-accessible it is a
        // potential security risk
        foreach ([$home, $cacheDir] as $dir) {
            if (!file_exists($dir.'/.htaccess')) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0744, true);
                }
                @file_put_contents($dir.'/.htaccess', 'Deny from all');
            }
        }

        $config = new Config();

        // add dirs to the config
        $config->merge(
            [
                'home' => $home,
                'home_config' => $home.'/.gush.yml',
                'cache-dir' => $cacheDir,
            ]
        );

        if ($loadLocal) {
            $config->merge(
                [
                    'local' => getcwd(),
                    'local_config' => getcwd().'/.gush.yml',
                ]
            );
        }

        if (true === $loadParameters) {
            self::readParameters($config);
        }

        return $config;
    }

    /**
     * @param Config $config
     *
     * @throws \RuntimeException
     * @throws FileNotFoundException
     */
    protected static function readParameters(Config $config)
    {
        $homeFilename = $config->get('home_config');
        $localFilename = $config->get('local_config');

        if (!file_exists($homeFilename)) {
            throw new FileNotFoundException(
                'The .gush.yml file doest not exist, please run the core:configure command.'
            );
        }

        static::loadAndMerge($config, $homeFilename);

        // merge the local config
        if (null !== $localFilename && file_exists($localFilename)) {
            static::loadAndMerge($config, $localFilename, null);
        }
    }

    protected static function loadAndMerge(Config $config, $filename, $rootKey = 'parameters')
    {
        try {
            $parsed = Yaml::parse(file_get_contents($filename));

            if ($rootKey) {
                $config->merge($parsed[$rootKey]);
            } else {
                $config->merge($parsed);
            }

            if (!$config->isValid()) {
                throw new \RuntimeException(
                    "Your '$filename' seem to be invalid. Errors: ".PHP_EOL.implode(PHP_EOL, $config->getErrorList())
                );
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException(
                $exception->getMessage().PHP_EOL.'Please run the core:configure command.',
                $exception->getCode(),
                $exception
            );
        }
    }
}
