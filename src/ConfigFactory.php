<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * The ConfigFactory helps to load and save the App configuration
 * to run Gush.
 */
class ConfigFactory
{
    /**
     * Get the home folder of Gush.
     *
     * This checks multiple environment variables:
     * * GUSH_HOME
     * * APPDATA (used by Windows)
     * * HOME
     *
     * @throws \RuntimeException when no environment variable was found
     * @throws IOException       the missing home-folder cannot be created
     *
     * @return string
     */
    public static function getHomedir()
    {
        $home = (string) getenv('GUSH_HOME');

        if ('' === $home) {
            // Try APPDATA first, HOME on Windows is only defined in the Git terminal
            // but when you run Gush using cmd it will not able to find the config-dir
            if (getenv('APPDATA')) {
                $home = strtr(getenv('APPDATA'), '\\', '/').'/Gush';
            } elseif (getenv('HOME')) {
                $home = (string) getenv('HOME').'/.gush';
            }
        }

        if ('' === $home) {
            throw new \RuntimeException(
                'Unable to determine the home folder of your Gush config.'."\n".
                'Neither the "GUSH_HOME", "HOME" or "APPDATA" (Windows only) environment variables were set.'."\n".
                'Set the "GUSH_HOME" environment variable for Gush to run correctly.'
            );
        }

        $fs = self::getFilesystem();

        if (!$fs->exists($home)) {
            $fs->mkdir($home, 0744);
        }

        return $home;
    }

    /**
     * Create a new Config object using the local filesystem.
     *
     * Note that any missing config file is ignored.
     *
     * When the home or cache folder doesn't exist it's created.
     *
     * @param string|null $localHome Local home folder to load extra configuration from
     *                               when null this is ignored
     *
     * @return Config
     */
    public static function createConfig($localHome = null)
    {
        $home = static::getHomedir();
        $cacheDir = self::getCacheDir($home);
        $localConfig = [];

        $systemConfig = self::loadFileOrEmpty($home.'/.gush.yml');

        if (null !== $localHome) {
            $localConfig = self::loadFileOrEmpty($localHome.'/.gush.yml');
        }

        return new Config($home, $cacheDir, $systemConfig, $localHome, $localConfig);
    }

    /**
     * Create a new Config object using the ENV configuration.
     *
     * Note that any missing config file is ignored.
     *
     * When the home or cache folder doesn't exist it's created.
     * This also ensures the directories are protected from web access.
     *
     * @param string $systemConfigEnv
     * @param string $localConfigEnv
     *
     * @return Config
     */
    public static function createConfigFromEnv($systemConfigEnv = null, $localConfigEnv = null)
    {
        $cacheDir = '/tmp';

        $systemConfig = [];
        $localConfig = [];

        if (!empty($localConfigEnv)) {
            $systemConfig = Yaml::parse((base64_decode($systemConfigEnv)));
        }

        if (!empty($localConfigEnv)) {
            $localConfig = Yaml::parse((base64_decode($localConfigEnv)));
        }

        return new Config(null, $cacheDir, $systemConfig, null, $localConfig);
    }

    /**
     * Dump configuration to the related config-file.
     *
     * The config-file is automatically determined using
     * type.
     *
     * @param Config $config
     * @param string $type
     *
     * @return string
     */
    public static function dumpToFile(Config $config, $type)
    {
        if ($type === Config::CONFIG_LOCAL) {
            $filename = $config->get('local_config');

            if (null === $filename) {
                throw new \RuntimeException('Local configuration is not loaded and therefor cannot be dumped.');
            }
        } elseif ($type === Config::CONFIG_SYSTEM) {
            $filename = $config->get('home_config');
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Config slot "%s" is not valid for dumping to a file "%s", use either: '.
                    'Config::CONFIG_SYSTEM or Config::CONFIG_LOCAL.',
                    $type
                )
            );
        }

        // Testing compatibility, write directly as there is no risk of problems
        if ('vfs://' === substr($filename, 0, 6)) {
            file_put_contents($filename, Yaml::dump($config->toArray($type)));
        } else {
            self::getFilesystem()->dumpFile(
                $filename,
                "# Gush configuration file, any comments will be lost.\n".Yaml::dump($config->toArray($type), 2)
            );
        }

        return $filename;
    }

    /**
     * @return Filesystem
     */
    private static function getFilesystem()
    {
        static $fs;

        if (null === $fs) {
            $fs = new Filesystem();
        }

        return $fs;
    }

    /**
     * @param string $homedir
     *
     * @return string
     */
    private static function getCacheDir($homedir)
    {
        if (!$cacheDir = (string) getenv('GUSH_CACHE_DIR')) {
            $cacheDir = $homedir.'/cache';
        }

        if (!file_exists($cacheDir)) {
            self::getFilesystem()->mkdir($cacheDir, 0744);
        }

        return $cacheDir;
    }

    /**
     * @param string $filename
     *
     * @return array
     */
    private static function loadFileOrEmpty($filename)
    {
        if (!file_exists($filename)) {
            return [];
        }

        try {
            if (!is_array($content = Yaml::parse(file_get_contents($filename)))) {
                $content = [];
            }

            return $content;
        } catch (ParseException $exception) {
            $errorFormatter = new OutputFormatter(true);

            echo $errorFormatter->format(
                    sprintf(
                        '<error>[WARNING] YAML File "%s" is invalid, falling back to "[]" as parsed-value.</error>'.
                        PHP_EOL.
                        '<error>Error: %s</error>'.
                        PHP_EOL,
                        $errorFormatter->escape($filename),
                        $errorFormatter->escape($exception->getMessage())
                    )
                ).PHP_EOL
            ;

            return [];
        }
    }
}
