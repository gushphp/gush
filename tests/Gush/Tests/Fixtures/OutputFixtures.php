<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Fixtures;

/**
 * Please do not autoedit this file thereby removing intentional white spaces
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class OutputFixtures
{
    const releaseList = <<<EOT
+-----+-------------------+----------+-----------+-------+------------+------------+------------+
| ID  | Name              | Tag      | Commitish | Draft | Prerelease | Created    | Published  |
+-----+-------------------+----------+-----------+-------+------------+------------+------------+
| 123 | This is a Release | Tag name | 123123    | yes   | yes        | 2014-01-05 | 2014-01-05 |
+-----+-------------------+----------+-----------+-------+------------+------------+------------+

1 release(s)
EOT;

    const issueShow = <<<EOT
Issue #60 (open): by weaverryan [cordoval]
Type: Pull Request
Milestone: Conquer the world
Labels: actionable, easy pick
Title: Write a behat test to launch strategy

Help me conquer the world. Teach them to use gush.
EOT;

    const issueList = <<<EOT
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+
| # | State | PR? | Title      | User       | Assignee | Milestone       | Labels           | Created    |
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+
| 1 | open  |     | easy issue | cordoval   | cordoval | some good st... | critic,easy pick | 1969-12-31 |
| 2 | open  |     | hard issue | weaverryan | cordoval | some good st... | critic           | 1969-12-31 |
+---+-------+-----+------------+------------+----------+-----------------+------------------+------------+

2 issues
EOT;

    const issueClose = <<<EOT
Closed https://github.com/cordoval/gush/issues/12
EOT;

}
