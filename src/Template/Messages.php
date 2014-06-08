<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template;

class Messages
{
    const MERGE = <<<EOT
{{ type }} #{{ prNumber }} {{ prTitle }} ({{ author }})

This PR was merged into {{ baseBranch }} branch.

Discussion
----------

{{ prBody }}

Commits
-------

{{ commits }}
EOT;

    const COMMENT = <<<EOT
---------------------------------------------------------------------------

by {{ login }} at {{ created_at }}

{{ body }}
\n
EOT;

    /**
     * @param string $name
     *
     * @return string
     */
    public static function get($name)
    {
        return constant('self::'.strtoupper($name));
    }
}
