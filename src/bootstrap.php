<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

function includeIfExists($file)
{
    return file_exists($file) ? include $file : false;
}

if (
    (!$loader = includeIfExists(__DIR__.'/../vendor/autoload.php')) &&
    (!$loader = includeIfExists(__DIR__.'/../../../autoload.php'))
) {
    throw new \RunTimeException('Cannot find an autoload.php file, have you executed composer install command?');
}

return $loader;
