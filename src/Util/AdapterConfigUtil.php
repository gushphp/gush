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

final class AdapterConfigUtil
{
    // Use a null character to ensure the name can never a legal name
    // and help with detecting its undefined
    const UNDEFINED_ORG = "org-autodetected\0";
    const UNDEFINED_REPO = "repo-autodetected\0";
    const UNDEFINED_ADAPTER = "adapter-autodetected\0";

    /**
     * @param string      $value
     * @param string|null $default
     *
     * @return null|string
     */
    public static function undefinedToDefault($value, $default = null)
    {
        if (false !== strpos($value, "\0")) {
            return $default;
        }

        return $value;
    }
}
