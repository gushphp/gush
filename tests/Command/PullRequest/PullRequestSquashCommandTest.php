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

use Gush\Command\PullRequest\PullRequestSquashCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;

class PullRequestSquashCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function squashes_commits_and_pushes_force_pull_request_branch()
    {
        $this->expectsConfig();

        $tester = $this->getCommandTester($command = new PullRequestSquashCommand());
        $command->getHelperSet()->set($this->expectGitHelper('base_ref', 'head_ref'));

        $tester->execute(['--org' => 'cordoval', '--repo' => 'gush', 'pr_number' => 40], ['interactive' => false]);

        $this->assertEquals(OutputFixtures::PULL_REQUEST_SQUASH, trim($tester->getDisplay(true)));
    }

    private function expectGitHelper()
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        $gitHelper->squashCommits('base_ref', 'head_ref')->shouldBeCalled();
        $gitHelper->pushToRemote('origin', 'head_ref', true, true)->shouldBeCalled();

        return $gitHelper->reveal();
    }
}
