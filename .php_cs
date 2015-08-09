<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->notName('OutputFixtures.php')
    ->notName('phar-stub.php')
    ->in(
        [
            __DIR__.'/src',
            __DIR__.'/tests',
        ]
    )
;

// Load a local config-file when existing
if (file_exists(__DIR__.'/local.php_cs')) {
    require __DIR__.'/local.php_cs';
}

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers(
        [
            'short_array_syntax',
            'ordered_use',
            '-psr0',
        ]
    )
    ->finder($finder)
;
