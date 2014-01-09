<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template;

class Messages
{
    const merge = <<<EOT
This PR was merged into {{ baseBranch }} branch.

Discussion
----------

{{ prTitle }}

{{ prBody }}

Commits
-------

{{ commits }}
EOT;

    public static function get($name)
    {
        return constant('self::'.$name);
    }
}
