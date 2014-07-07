<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\PullRequest\PullRequestMergeCommand;

class PullRequestMergeCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester(new PullRequestMergeCommand());
        $tester->execute(['--org' => 'gushphp', 'pr_number' => 40, '--no-comments' => true], ['interactive' => false]);

        $this->assertEquals('Pull Request successfully merged.', trim($tester->getDisplay(true)));
    }
}
