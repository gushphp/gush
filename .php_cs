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
            'line_after_namespace',
            'multiple_use',
            'concat_without_spaces',
            'operators_spaces',
            'single_array_no_trailing_comma',
            'whitespacy_lines',
            // 'strict',
            '-psr0',
        ]
    )
    ->finder($finder)
;
