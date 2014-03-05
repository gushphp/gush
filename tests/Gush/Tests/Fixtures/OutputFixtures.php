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

    const PULL_REQUEST_FIXER = <<<EOT
CS fix committed and pushed!
EOT;

    const PULL_REQUEST_PAT_ON_THE_BACK = <<<EOT
Pat on the back pushed to https://github.com/gushphp/gush/pull/40
EOT;

    const PULL_REQUEST_VERSIONEYE = <<<EOT
OUT > ./composer.json has been updated
OUT > ./composer.json has been updated
Please check the modifications on your composer.json for
updated dependencies.
EOT;

    const PULL_REQUEST_SQUASH = <<<EOT
PR has been squashed!
EOT;
}
