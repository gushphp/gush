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

use Gush\Command\PullRequest\PullRequestListCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class PullRequestListCommandTest extends CommandTestCase
{
    public function testListsPullRequests()
    {
        $tester = $this->getCommandTester(new PullRequestListCommand());
        $tester->execute();

        $this->assertCommandOutputMatches('1 pull request(s)', $tester->getDisplay());
        $this->assertTableOutputMatches(
            ['ID', 'Title', 'State', 'Created', 'User', 'Link'],
            [
                ['17', 'New feature added', 'Open', '2014-04-14 17:24', 'pierredup', 'https://github.com/gushphp/gush/pull/17'],
            ],
            $tester->getDisplay()
        );
    }
}
