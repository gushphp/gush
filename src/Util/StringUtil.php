<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
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
    public static function splitLines($input)
    {
        $input = trim($input);

        return ((string) $input === '') ? [] : preg_split('{\r?\n}', $input);
    }

    /**
     * Concatenates the words to an uppercased wording.
     *
     * Converts 'git-flow' to 'GitFlow'.
     *
     * @param string $word The word to transform.
     *
     * @return string The transformed word.
     */
    public static function concatWords($word)
    {
        return str_replace(' ', '', ucwords(strtr($word, '_-', '  ')));
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
    public static function camelize($word)
    {
        return lcfirst(self::concatWords($word));
    }
}
