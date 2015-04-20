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

use Gush\Command\Branch\BranchSyncCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;

class BranchSyncCommandTest extends CommandTestCase
{
    const TEST_BRANCH_NAME = 'test_branch';

    /**
     * @test
     */
    public function syncs_current_branch_with_origin()
    {
        $tester = $this->getCommandTester($command = new BranchSyncCommand());
        $command->getHelperSet()->set($this->expectGitHelper());

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(
            sprintf(OutputFixtures::BRANCH_SYNC, self::TEST_BRANCH_NAME, 'origin'),
            trim($tester->getDisplay(true))
        );
    }

    /**
     * @test
     */
    public function syncs_specific_branch_with_origin()
    {
        $branch = 'development';
        $remote = 'origin';

        $tester = $this->getCommandTester($command = new BranchSyncCommand());
        $command->getHelperSet()->set($this->expectGitHelper($remote, $branch));

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'branch_name' => $branch],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf(OutputFixtures::BRANCH_SYNC, $branch, 'origin'),
            trim($tester->getDisplay(true))
        );
    }

    /**
     * @test
     */
    public function syncs_specific_branch_with_specific_remote()
    {
        $branch = 'development';
        $remote = 'upstream';

        $tester = $this->getCommandTester($command = new BranchSyncCommand());
        $command->getHelperSet()->set($this->expectGitHelper($remote, $branch));

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'branch_name' => $branch, 'remote' => $remote],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf(OutputFixtures::BRANCH_SYNC, $branch, $remote),
            trim($tester->getDisplay(true))
        );
    }

    private function expectGitHelper($remote = 'origin', $branch = self::TEST_BRANCH_NAME)
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        $gitHelper->getActiveBranchName()->willReturn($branch);
        $gitHelper->syncWithRemote($remote, $branch)->shouldBeCalled();

        return $gitHelper->reveal();
    }
}
