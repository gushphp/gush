<?php

$file = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($file)) {
    echo <<<EOT
You need to install the project dependencies using Composer:
 $ wget http://getcomposer.org/composer.phar
Or
 $ curl -s https://getcomposer.org/installer | php
 $ php composer.phar install --dev
 $ phpunit\n
EOT;
    exit(1);
}

$loader = require $file;

$loader->add('Gush\Tests', __DIR__);
