<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestCloseCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class PullRequestCloseCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function closes_a_pull_request()
    {
        $tester = $this->getCommandTester(new PullRequestCloseCommand());
        $tester->execute(
            ['--org' => 'gushphp', 'pr_number' => TestAdapter::PULL_REQUEST_NUMBER],
            ['interactive' => false]
        );

        $this->assertEquals(OutputFixtures::PULL_REQUEST_CLOSE, trim($tester->getDisplay(true)));
    }
}
