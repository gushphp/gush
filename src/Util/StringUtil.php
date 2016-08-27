<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Util;

/**
 * String utilities class.
 *
 * Some methods in this class are borrowed from the Doctrine project.
 */
final class StringUtil
{
    public static function splitLines(string $input): array
    {
        $input = trim($input);

        return ('' === $input) ? [] : preg_split('{\r?\n}', $input);
    }

    /**
     * Concatenates the words to an uppercased wording.
     *
     * Converts 'git flow', 'git-flow' and 'git_flow' to 'GitFlow'.
     *
     * @param string $word The word to transform.
     *
     * @return string The transformed word.
     */
    public static function concatWords(string $word): string
    {
        return str_replace([' ', '-', '_'], '', ucwords($word, '_- '));
    }

    /**
     * Camelizes a word.
     *
     * This uses the classify() method and turns the first character to lowercase.
     *
     * @param string $word The word to camelize.
     *
     * @return string The camelized word.
     */
    public static function camelize(string $word): string
    {
        return lcfirst(self::concatWords($word));
    }
}
