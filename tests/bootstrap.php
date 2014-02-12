<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$file = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($file)) {
    echo <<<EOT
You need to install the project dependencies using Composer:
 $ wget http://getcomposer.org/composer.phar
Or
 $ curl -s https://getcomposer.org/installer | php
 $ php composer.phar install
 $ phpunit\n
EOT;
    exit(1);
}

$loader = require $file;
date_default_timezone_set('America/Los_Angeles');

$loader->add('Gush\\Tests\\', __DIR__);
