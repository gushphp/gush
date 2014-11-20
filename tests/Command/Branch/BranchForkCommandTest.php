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

use Gush\Command\Branch\BranchForkCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;

class BranchForkCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function forks_repository_to_users_vendor_name()
    {
        $this->expectsConfig();

        $tester = $this->getCommandTester($command = new BranchForkCommand());
        $command->getHelperSet()->set($this->expectGitHelper('cordoval', 'git@github.com:cordoval/gush.git'));

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(sprintf(OutputFixtures::BRANCH_FORK, 'cordoval'), trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function forks_repository_to_specific_vendor_name()
    {
        $this->expectsConfig();

        $tester = $this->getCommandTester($command = new BranchForkCommand());
        $command->getHelperSet()->set($this->expectGitHelper('someone', 'git@github.com:cordoval/gush.git'));

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'other_organization' => 'someone'],
            ['interactive' => false]
        );

        $this->assertEquals(sprintf(OutputFixtures::BRANCH_FORK, 'someone'), trim($tester->getDisplay(true)));
    }

    private function expectGitHelper($remoteName, $gitUrl)
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        $gitHelper->addRemote($remoteName, $gitUrl)->shouldBeCalled();

        return $gitHelper->reveal();
    }
}
