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

class BranchDeleteCommandTest extends BaseTestCase
{
    const TEST_BRANCH_NAME = 'test_branch';

    /**
     * @test
     */
    public function deletes_current_remote_branch()
    {
        $processHelper = $this->expectProcessHelper();
        $gitHelper = $this->expectGitHelper();

        $this->expectsConfig();
        $tester = $this->getCommandTester($command = new BranchDeleteCommand());
        $command->getHelperSet()->set($processHelper, 'process');
        $command->getHelperSet()->set($gitHelper, 'git');

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(OutputFixtures::BRANCH_DELETE, trim($tester->getDisplay(true)));
    }

    private function expectProcessHelper()
    {
        $processHelper = $this->getMock(
            'Gush\Helper\ProcessHelper',
            ['runCommands']
        );
        $processHelper->expects($this->once())
            ->method('runCommands')
            ->with([
                [
                    'line' => 'git push -u cordoval :'.self::TEST_BRANCH_NAME,
                    'allow_failures' => true
                ],
            ])
        ;

        return $processHelper;
    }

    private function expectGitHelper()
    {
        $gitHelper = $this
            ->getMockBuilder('Gush\Helper\GitHelper')
            ->disableOriginalConstructor()
            ->setMethods(['getActiveBranchName'])
            ->getMock()
        ;
        $gitHelper->expects($this->once())
            ->method('getActiveBranchName')
            ->will($this->returnValue(self::TEST_BRANCH_NAME))
        ;

        return $gitHelper;
    }

    private function expectsConfig()
    {
        $this->config
            ->expects($this->at(0))
            ->method('get')
            ->with('adapter')
            ->will($this->returnValue('github_enterprise'))
        ;
        $this->config
            ->expects($this->at(1))
            ->method('get')
            ->with('[adapters][github_enterprise][authentication]')
            ->will($this->returnValue(['username' => 'cordoval']))
        ;
    }
}
