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

final class CliValidator
{
    public static function notEmpty($value)
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('Value cannot be empty.');
        }

        return $value;
    }
}
