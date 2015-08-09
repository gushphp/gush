<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$header = <<<EOF
This file is part of Gush package.

(c) Luis Cordova <cordoval@gmail.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

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
            'header_comment',
            'ordered_use',
            'short_array_syntax',
            '-psr0',
        ]
    )
    ->finder($finder)
;
