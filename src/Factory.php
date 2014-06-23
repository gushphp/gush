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

use Gush\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates the default directory structure to run Gush
 */
class Factory
{
    /**
     * @param bool $loadParameters If false, will return an empty config object
     *
     * @throws \RuntimeException
     * @return Config
     */
    public static function createConfig($loadParameters = true)
    {
        // determine home and cache dirs
        $home = getenv('GUSH_HOME');
        $cacheDir = getenv('GUSH_CACHE_DIR');
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
                'home'         => $home,
                'home_config'  => $home.'/.gush.yml',
                'cache-dir'    => $cacheDir,
                'local'        => getcwd(),
                'local_config' => getcwd().'/.gush.yml',
            ]
        );

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

        try {
            $parsed = Yaml::parse($homeFilename);

            // Don't overwrite local config
            unset($parsed['parameters']['local'], $parsed['parameters']['local_config']);

            $config->merge($parsed['parameters']);

            if (!$config->isValid()) {
                throw new \RuntimeException(
                    'The .gush.yml is not properly configured.'
                );
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException(
                $exception->getMessage().PHP_EOL.'Please run the core:configure command.',
                $exception->getCode(),
                $exception
            );
        }

        // merge the local config
        if (file_exists($localFilename)) {
            try {
                $parsed = Yaml::parse($localFilename);
                $config->merge($parsed);
            } catch (\Exception $exception) {
                throw new \RuntimeException(
                    $exception->getMessage().PHP_EOL.'Please run the core:configure command.',
                    $exception->getCode(),
                    $exception
                );
            }
        }
    }
}
