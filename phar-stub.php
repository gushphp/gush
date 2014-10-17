<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (class_exists('Phar')) {
    Phar::mapPhar('gush.phar');

    // Copy the cacert.pem file from the phar if it is not in the temp folder.
    $from = 'phar://gush.phar/vendor/guzzle/guzzle/src/Guzzle/Http/Resources/cacert.pem';
    $certFile = sys_get_temp_dir().'/guzzle-cacert.pem';

    // Only copy when the file size is different
    if (!file_exists($certFile) || filesize($certFile) != filesize($from)) {
        if (!copy($from, $certFile)) {
            throw new RuntimeException("Could not copy {$from} to {$certFile}: "
                .var_export(error_get_last(), true));
        }
    }

    require 'phar://'.__FILE__.'/bin/gush';
}
__HALT_COMPILER(); ?>
