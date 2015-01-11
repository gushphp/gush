<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template;

class Messages
{
    const MERGE = <<<EOT
{{ type }} #{{ prNumber }} {{ prTitle }} ({{ authors }})

{{ mergeNote }}

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

    const MERGE_NOTE_NORMAL = <<<EOT
This PR was merged into the {{ baseBranch }} branch.
EOT;

    const MERGE_NOTE_SWITCHED_BASE = <<<EOT
This PR was submitted for the {{ originalBaseBranch }} branch but it was merged into the {{ targetBaseBranch }} branch instead (closes #{{ prNumber }}).
EOT;

    const MERGE_NOTE_SQUASHED = <<<EOT
This PR was squashed before being merged into the {{ baseBranch }} branch (closes #{{ prNumber }}).
EOT;

    const MERGE_NOTE_SWITCHED_BASE_AND_SQUASHED = <<<EOT
This PR was submitted for the {{ originalBaseBranch }} branch but it was squashed and merged into the {{ targetBaseBranch }} branch instead (closes #{{ prNumber }}).
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
