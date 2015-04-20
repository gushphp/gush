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

use Gush\Command\PullRequest\PullRequestFixerCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;

class PullRequestFixerCommandTest extends CommandTestCase
{
    const TEST_BRANCH_NAME = 'test_branch';

    /**
     * @test
     */
    public function fixes_coding_style_on_current_branch_pull_requested()
    {
        $tester = $this->getCommandTester($command = new PullRequestFixerCommand());
        $command->getHelperSet()->set($this->expectProcessHelper());
        $command->getHelperSet()->set($this->expectGitHelper());

        $tester->execute([]);

        $this->assertEquals(OutputFixtures::PULL_REQUEST_FIXER, trim($tester->getDisplay(true)));
    }

    private function expectProcessHelper()
    {
        $processHelper = $this->prophet->prophesize('Gush\Helper\ProcessHelper');
        $processHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $processHelper->setOutput(Argument::any())->shouldBeCalled();
        $processHelper->getName()->willReturn('process');

        $processHelper->probePhpCsFixer()->willReturn('php-cs-fixer');
        $processHelper->runCommand('php-cs-fixer fix .', true)->shouldBeCalled();

        return $processHelper->reveal();
    }

    private function expectGitHelper($wcReady = false, $hasChanges = true)
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        if ($wcReady) {
            $gitHelper->isWorkingTreeReady()->willReturn(true);
        } else {
            $gitHelper->isWorkingTreeReady()->willReturn(false);
            $gitHelper->commit('wip', ['a'])->shouldBeCalled();
        }

        $gitHelper->add('.')->shouldBeCalled();

        if ($hasChanges) {
            $gitHelper->isWorkingTreeReady(true)->willReturn(false);
            $gitHelper->commit('cs-fixer', ['a'])->shouldBeCalled();
        } else {
            $gitHelper->isWorkingTreeReady(true)->willReturn(true);
        }

        return $gitHelper->reveal();
    }
}
