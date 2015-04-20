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
    /**
     * @test
     */
    public function lists_pull_requests()
    {
        $tester = $this->getCommandTester(new PullRequestListCommand());
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(trim(OutputFixtures::PULL_REQUEST_LIST), trim($tester->getDisplay(true)));
    }
}
