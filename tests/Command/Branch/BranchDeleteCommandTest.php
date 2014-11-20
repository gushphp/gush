<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Branch;

use Gush\Command\Branch\BranchDeleteCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;

class BranchDeleteCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function deletes_current_remote_branch()
    {
        $org = 'cordoval';
        $branch = 'test_branch';

        $gitHelper = $this->expectGitHelper($branch);

        $this->expectsConfig($org);

        $tester = $this->getCommandTester($command = new BranchDeleteCommand());
        $command->getHelperSet()->set($gitHelper);

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(sprintf(OutputFixtures::BRANCH_DELETE, $org, $branch), trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function deletes_specific_remote_branch()
    {
        $org = 'cordoval';
        $branch = 'foo';

        $gitHelper = $this->expectGitHelper($branch);

        $this->expectsConfig($org);

        $tester = $this->getCommandTester($command = new BranchDeleteCommand());
        $command->getHelperSet()->set($gitHelper);

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'branch_name' => $branch],
            ['interactive' => false]
        );

        $this->assertEquals(sprintf(OutputFixtures::BRANCH_DELETE, $org, $branch), trim($tester->getDisplay(true)));
    }

    private function expectGitHelper($branchName)
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        $gitHelper->getActiveBranchName()->willReturn($branchName);
        $gitHelper->pushRemote('cordoval', ':'.$branchName, true)->shouldBeCalled();

        return $gitHelper->reveal();
    }
}
