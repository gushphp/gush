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

use Gush\Command\PullRequest\PullRequestFixerCommand;
use Gush\Helper\GitHelper;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestFixerCommandTest extends CommandTestCase
{
    public function testFixCodingStyleOnCurrentBranch()
    {
        $command = new PullRequestFixerCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(true, false)->reveal());
            }
        );
        $tester->execute([]);

        $this->assertCommandOutputMatches('CS fixes committed!', $tester->getDisplay());
    }

    public function testFixCodingStyleOnCurrentBranchNoChanges()
    {
        $command = new PullRequestFixerCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(true, false)->reveal());
            }
        );

        $tester->execute([]);

        $this->assertCommandOutputMatches('CS fixes committed!', $tester->getDisplay());
    }

    public function testWipCommitCurrentChangesAndFixCodingStyleOnCurrentBranch()
    {
        $command = new PullRequestFixerCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(false)->reveal());
            }
        );

        $tester->execute([]);

        $this->assertCommandOutputMatches(
            [
                'Your working tree has uncommitted changes, committing changes with "WIP" as message.',
                'CS fixes committed!',
            ],
            $tester->getDisplay()
        );
    }

    protected function getProcessHelper()
    {
        $processHelper = parent::getProcessHelper();
        $processHelper->probePhpCsFixer()->willReturn('php-cs-fixer');
        $processHelper->runCommand('php-cs-fixer fix .', true)->shouldBeCalled();

        return $processHelper;
    }

    private function getLocalGitHelper($wcReady = true, $hasChanges = true)
    {
        $gitHelper = parent::getGitHelper();

        if ($wcReady) {
            $gitHelper->isWorkingTreeReady()->willReturn(true);
            $gitHelper->commit('wip', GitHelper::COMMIT_ALL)->shouldNotBeCalled();
        } else {
            $gitHelper->isWorkingTreeReady()->willReturn(false);
            $gitHelper->commit('wip', GitHelper::COMMIT_ALL)->shouldBeCalled();
        }

        $gitHelper->add('.')->shouldBeCalled();

        if ($hasChanges) {
            $gitHelper->isWorkingTreeReady(true)->willReturn(false);
            $gitHelper->commit('cs-fixer', GitHelper::COMMIT_ALL)->shouldBeCalled();
        } else {
            $gitHelper->isWorkingTreeReady(true)->willReturn(true);
            $gitHelper->commit('cs-fixer', GitHelper::COMMIT_ALL)->shouldNotBeCalled();
        }

        return $gitHelper;
    }
}
