<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Util;

/**
 * ArrayUtil class.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ArrayUtil
{
    public static function getValuesFromNestedArray(array $array, $key)
    {
        $values = [];

        foreach ($array as $item) {
            $values = $item[$key];
        }

        return $values;
    }
}
