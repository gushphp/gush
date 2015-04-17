<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Fixtures;

/**
 * Please do not auto-edit this file thereby removing intentional white spaces
 */
class OutputFixtures
{
    const RELEASE_LIST = <<<EOT
+----+--------+--------+-------+------------+------------------+------------------+
| ID | Name   | Tag    | Draft | Prerelease | Created          | Published        |
+----+--------+--------+-------+------------+------------------+------------------+
| 1  | v1.0.0 | v1.0.0 | no    | no         | 2014-01-05 10:00 | 2014-01-05 10:00 |
+----+--------+--------+-------+------------+------------------+------------------+

1 release(s)
EOT;

    const PULL_REQUEST_LIST = <<<EOT
+----+-------------------+-------+------------------+-----------+-----------------------------------------+
| ID | Title             | State | Created          | User      | Link                                    |
+----+-------------------+-------+------------------+-----------+-----------------------------------------+
| 17 | New feature added | Open  | 2014-04-14 17:24 | pierredup | https://github.com/gushphp/gush/pull/17 |
+----+-------------------+-------+------------------+-----------+-----------------------------------------+

1 pull request(s)
EOT;

    const ISSUE_SHOW = <<<EOT
Issue #60 (open): by weaverryan [cordoval]
Type: Pull Request
Milestone: v1.0
Labels: actionable, easy pick
Title: Write a behat test to launch strategy
Link: https://github.com/gushphp/gush/issues/60

Help me conquer the world. Teach them to use Gush.
EOT;

    const ISSUE_LIST_ALL = <<<EOT
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------------+------------------------------------------+
| # | State | PR? | Title      | User       | Assignee | Milestone       | Labels           | Created          | Link                                     |
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------------+------------------------------------------+
| 1 | open  | PR  | easy issue | cordoval   | cordoval | good_release    | critic,easy pick | 1969-12-31 10:00 | https://github.com/gushphp/gush/issues/1 |
| 2 | open  |     | hard issue | weaverryan | cordoval | some_good_stuff | critic           | 1969-12-31 10:00 | https://github.com/gushphp/gush/issues/2 |
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------------+------------------------------------------+

2 issues
EOT;

    const ISSUE_LIST_NO_PR = <<<EOT
+---+-------+-----+------------+------------+----------+-----------------+--------+------------------+------------------------------------------+
| # | State | PR? | Title      | User       | Assignee | Milestone       | Labels | Created          | Link                                     |
+---+-------+-----+------------+------------+----------+-----------------+--------+------------------+------------------------------------------+
| 2 | open  |     | hard issue | weaverryan | cordoval | some_good_stuff | critic | 1969-12-31 10:00 | https://github.com/gushphp/gush/issues/2 |
+---+-------+-----+------------+------------+----------+-----------------+--------+------------------+------------------------------------------+

1 issues
EOT;

    const ISSUE_CLOSE = <<<EOT
[OK] Closed https://github.com/gushphp/gush/issues/7
EOT;

    const ISSUE_COPY = <<<EOT
[OK] Opened issue: https://github.com/gushphp/gush/issues/77

[OK] Closed issue: https://github.com/gushphp/gush/issues/7
EOT;

    const PULL_REQUEST_CLOSE = <<<EOT
[OK] Closed https://github.com/gushphp/gush/pull/40
EOT;

    const BRANCH_SYNC = <<<EOT
[OK] Branch "%s" has been synced with remote "%s".
EOT;

    const BRANCH_DELETE = <<<EOT
[OK] Branch %s/%s has been deleted!
EOT;

    const BRANCH_FORK = <<<EOT
[OK] Forked repository gushphp/gush into %s/gush
EOT;

    const BRANCH_CHANGELOG_EMPTY = <<<EOT
There were no tags found
EOT;

    const BRANCH_CHANGELOG = <<<EOT
#123: Write a behat test to launch strategy   https://github.com/gushphp/gush/issues/123
EOT;

    const BRANCH_PUSH = <<<EOT
[OK] Branch pushed to %s/%s
EOT;

    const PULL_REQUEST_FIXER = <<<EOT
[OK] CS fixes committed!
EOT;

    const PULL_REQUEST_PAT_ON_THE_BACK = <<<EOT
[OK] Pat on the back pushed to https://github.com/gushphp/gush/pull/40
EOT;

    const PULL_REQUEST_SQUASH = <<<EOT
[OK] Pull request has been squashed!
EOT;

    const HEADER_LICENSE_TWIG = <<<EOT
{#
 # This file is part of Your Package package.
 #
 # (c) 2009-2015 You <you@yourdomain.com>
 #
 # This source file is subject to the MIT license that is bundled
 # with this source code in the file LICENSE.
 #}

{% extends "base.twig" %}

{% block myBody %}
    <div class="someDiv">
        Some Content
    </div>
{% endblock myBody %}

EOT;

    const HEADER_LICENSE_PHP = <<<EOT
<?php

/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2015 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Test;

class MetaTest
{
    private \$test;

    public function __construct(\$test)
    {
        \$this->test = \$test;
    }
}

EOT;

    const HEADER_LICENSE_JS = <<<EOT
/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2015 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function ($) {
    $.fn.someFunction = function () {
        return $(this).append('New Function');
    };
})(window.jQuery);

EOT;

    const HEADER_LICENSE_CSS = <<<EOT
/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2015 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

.someDiv {
    background: #ff0000;
}

a#someId {
    color: #000000;
}

EOT;

    const AUTOCOMPLETE_SCRIPT = <<<EOT
#!/bin/sh
_gush()
{
    local cur prev coms opts
    COMPREPLY=()
    cur="\${COMP_WORDS[COMP_CWORD]}"
    prev="\${COMP_WORDS[COMP_CWORD-1]}"
    coms="test:command"
    opts="--stable --org"

    if [[ \${COMP_CWORD} = 1 ]] ; then
        COMPREPLY=($(compgen -W "\${coms}" -- \${cur}))

        return 0
    fi

    case "\${prev}" in

        esac

    COMPREPLY=($(compgen -W "\${opts}" -- \${cur}))

    return 0;
}

complete -o default -F _gush gush gush.phar
COMP_WORDBREAKS=\${COMP_WORDBREAKS//:}

EOT;
}
