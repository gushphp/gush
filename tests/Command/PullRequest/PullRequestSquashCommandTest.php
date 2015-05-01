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

use Gush\Command\PullRequest\PullRequestSquashCommand;
use Gush\Tests\Command\CommandTestCase;

class PullRequestSquashCommandTest extends CommandTestCase
{
    public function testSquashesCommitsAndForcePushesBranch()
    {
        $tester = $this->getCommandTester(new PullRequestSquashCommand());
        $tester->execute(['pr_number' => 10]);

        $this->assertCommandOutputMatches('Pull-request has been squashed!', $tester->getDisplay());
    }

    protected function getGitHelper($isGitFolder = true)
    {
        $gitHelper = parent::getGitHelper($isGitFolder);
        $gitHelper->squashCommits('base_ref', 'head_ref')->shouldBeCalled();
        $gitHelper->pushToRemote('origin', 'head_ref', false, true)->shouldBeCalled();

        return $gitHelper;
    }
}
