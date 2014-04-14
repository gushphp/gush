<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
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
+-----+-------------------+----------+-----------+-------+------------+------------+------------+
| ID  | Name              | Tag      | Commitish | Draft | Prerelease | Created    | Published  |
+-----+-------------------+----------+-----------+-------+------------+------------+------------+
| 123 | This is a Release | Tag name | 123123    | yes   | yes        | 2014-01-05 | 2014-01-05 |
+-----+-------------------+----------+-----------+-------+------------+------------+------------+

1 release(s)
EOT;

    const ISSUE_SHOW = <<<EOT
Issue #60 (open): by weaverryan [cordoval]
Type: Pull Request
Milestone: Conquer the world
Labels: actionable, easy pick
Title: Write a behat test to launch strategy

Help me conquer the world. Teach them to use gush.
EOT;

    const ISSUE_LIST = <<<EOT
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+
| # | State | PR? | Title      | User       | Assignee | Milestone       | Labels           | Created    |
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+
| 1 | open  |     | easy issue | cordoval   | cordoval | some good st... | critic,easy pick | 1969-12-31 |
| 2 | open  |     | hard issue | weaverryan | cordoval | some good st... | critic           | 1969-12-31 |
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+

2 issues
EOT;

    const ISSUE_CLOSE = <<<EOT
Closed https://github.com/gushphp/gush/issues/7
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

   const META_HEADER_TWIG = <<<EOT
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

   const META_HEADER_PHP = <<<EOT
<?php

/**
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class MetaTest
{
    private \$test;

    public function __construct(\$test)
    {
        \$this->test = \$test;
    }
}
EOT;

   const META_HEADER_JS = <<<EOT
/**
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function($){
    $.fn.someFunction = function(){
        return $(this).append('New Function');
    };
})(window.jQuery);
EOT;

   const META_HEADER_CSS = <<<EOT
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