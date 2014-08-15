<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->notName('LICENSE')
    ->notName('README.md')
    ->notName('.php_cs')
    ->notName('composer.*')
    ->notName('phpunit.xml*')
    ->notName('box.json')
    ->notName('*.phar')
    ->notName('installer')
    ->notName('OutputFixtures.php')
    ->exclude('web')
    ->in(__DIR__)
;

// Load a local config-file when existing
if (file_exists(__DIR__.'/local.php_cs')) {
    require __DIR__.'/local.php_cs';
}

return Symfony\CS\Config\Config::create()
    ->fixers(
        [
            'encoding',
            'linefeed',
            'indentation',
            'trailing_spaces',
            'object_operator',
            'phpdoc_params',
            'visibility',
            'short_tag',
            'php_closing_tag',
            'return',
            'extra_empty_lines',
            'braces',
            'lowercase_constants',
            'lowercase_keywords',
            'include',
            'function_declaration',
            'controls_spaces',
            'spaces_cast',
            'elseif',
            'eof_ending',
            'one_class_per_file',
            'unused_use',
            'ternary_spaces',
            'short_array_syntax',
            'standardize_not_equal',
            'new_with_braces',
            'ordered_use',
            'default_values',
        ]
    )
    ->finder($finder)
;
