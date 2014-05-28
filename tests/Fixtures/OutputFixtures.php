<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Fixtures;

/**
 * Please do not auto-edit this file thereby removing intentional white spaces
 *
 * @author Luis Cordova <cordoval@gmail.com>
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
+----+-------------------+-------+---------------------+-----------+-----------------------------------------+
| ID | Title             | State | Created             | User      | Link                                    |
+----+-------------------+-------+---------------------+-----------+-----------------------------------------+
| 17 | New feature added | Open  | 2014-04-14 17:24:12 | pierredup | https://github.com/gushphp/gush/pull/17 |
+----+-------------------+-------+---------------------+-----------+-----------------------------------------+

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

    const ISSUE_LIST = <<<EOT
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+------------------------------------------+
| # | State | PR? | Title      | User       | Assignee | Milestone       | Labels           | Created    | Link                                     |
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+------------------------------------------+
| 1 | open  |     | easy issue | cordoval   | cordoval | some good st... | critic,easy pick | 1969-12-31 | https://github.com/gushphp/gush/issues/1 |
| 2 | open  |     | hard issue | weaverryan | cordoval | some good st... | critic           | 1969-12-31 | https://github.com/gushphp/gush/issues/2 |
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+------------------------------------------+

2 issues
EOT;

    const ISSUE_CLOSE = <<<EOT
Closed https://github.com/gushphp/gush/issues/7
EOT;

    const PULL_REQUEST_CLOSE = <<<EOT
Closed https://github.com/gushphp/gush/pull/40
EOT;

    const BRANCH_SYNC = <<<EOT
Branch test_branch has been synced upstream!
EOT;

    const BRANCH_DELETE = <<<EOT
Branch cordoval/test_branch has been deleted!
EOT;

    const BRANCH_FORK = <<<EOT
Forked repository gushphp/gush into cordoval/gush
EOT;

    const BRANCH_CHANGELOG_EMPTY = <<<EOT
There were no tags found
EOT;

    const BRANCH_CHANGELOG = <<<EOT
123: Write a behat test to launch strategy   https://github.com/gushphp/gush/issues/123
EOT;

    const BRANCH_PUSH = <<<EOT
Branch pushed to cordoval/some-branch
EOT;

    const PULL_REQUEST_FIXER = <<<EOT
CS fix committed and pushed!
EOT;

    const PULL_REQUEST_PAT_ON_THE_BACK = <<<EOT
Pat on the back pushed to https://github.com/gushphp/gush/pull/40
EOT;

    const PULL_REQUEST_VERSIONEYE = <<<EOT
Please check the modifications on your composer.json for
updated dependencies.
EOT;

    const PULL_REQUEST_SQUASH = <<<EOT
PR has been squashed!
EOT;

    const HEADER_LICENSE_TWIG = <<<EOT
{##
 # This file is part of Your Package package.
 #
 # (c) 2009-2014 You <you@yourdomain.com>
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

/**
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
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
/**
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
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
/**
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
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
}
