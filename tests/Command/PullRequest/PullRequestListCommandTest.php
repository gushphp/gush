<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestListCommand;
use Gush\Tests\Command\CommandTestCase;

class PullRequestListCommandTest extends CommandTestCase
{
    public function testListsPullRequests()
    {
        $tester = $this->getCommandTester(new PullRequestListCommand());
        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(['Pull requests on gushphp/gush', '1 pull request(s)'], $display);
        $this->assertTableOutputMatches(
            ['ID', 'Title', 'State', 'Created', 'User', 'Link'],
            [
                ['17', 'New feature added', 'Open', '2014-04-14 17:24', 'pierredup', 'https://github.com/gushphp/gush/pull/17'],
            ],
            $display
        );
    }
}
