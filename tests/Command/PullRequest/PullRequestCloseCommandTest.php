<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestCloseCommand;
use Gush\Tests\Command\CommandTestCase;

class PullRequestCloseCommandTest extends CommandTestCase
{
    public function testClosePullRequest()
    {
        $tester = $this->getCommandTester(new PullRequestCloseCommand());
        $tester->execute(
            ['pr_number' => 10]
        );

        $this->assertCommandOutputMatches('Closed https://github.com/gushphp/gush/pull/10', $tester->getDisplay());
    }
}
