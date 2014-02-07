<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\Pats;

class Pats
{
    const SIZE = 7;

    const PAT1 = <<<EOT
Good job @{{ author }}.
EOT;

    const PAT2 = <<<EOT
You were fast on this one, thanks @{{ author }}.
EOT;

    const PAT3 = <<<EOT
Good catch @{{ author }}, thanks for the patch.
EOT;

    const PAT4 = <<<EOT
Thank you @{{ author }}.
EOT;

    const PAT5 = <<<EOT
Good catch, thanks @{{ author }}.
EOT;

    const PAT6 = <<<EOT
Thanks @{{ author }} for the pull request!
EOT;

    const PAT7 = <<<EOT
Well done @{{ author }}.
EOT;

    public static function get($name)
    {
        return constant('self::'.strtoupper($name));
    }

    public static function getRandom()
    {
        return self::get('PAT'.rand(1, self::SIZE));
    }
}
