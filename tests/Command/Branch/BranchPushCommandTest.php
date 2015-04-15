<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Branch;

use Gush\Command\Branch\BranchPushCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;

class BranchPushCommandTest extends BaseTestCase
{
    const TEST_BRANCH = 'test_branch';

    /**
     * @test
     */
    public function pushes_branch_to_fork()
    {
        $this->expectsConfig();

        $tester = $this->getCommandTester($command = new BranchPushCommand());
        $command->getHelperSet()->set($this->expectGitHelper());

        $tester->execute(['--org' => 'cordoval', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(
            sprintf(OutputFixtures::BRANCH_PUSH, 'cordoval', self::TEST_BRANCH),
            trim($tester->getDisplay(true))
        );
    }

    /**
     * @test
     */
    public function pushes_branch_to_specific_fork()
    {
        $this->expectsConfig();

        $tester = $this->getCommandTester($command = new BranchPushCommand());
        $command->getHelperSet()->set($this->expectGitHelper('somewhere'));

        $tester->execute(
            ['--org' => 'cordoval', '--repo' => 'gush', 'other_organization' => 'somewhere'],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf(OutputFixtures::BRANCH_PUSH, 'somewhere', self::TEST_BRANCH),
            trim($tester->getDisplay(true))
        );
    }

    private function expectGitHelper($org = 'cordoval')
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        $gitHelper->getActiveBranchName()->willReturn(self::TEST_BRANCH);
        $gitHelper->pushToRemote($org, self::TEST_BRANCH, true)->shouldBeCalled();

        return $gitHelper->reveal();
    }
}
