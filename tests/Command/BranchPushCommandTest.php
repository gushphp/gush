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

use Gush\Command\Branch\BranchPushCommand;
use Gush\Tests\Fixtures\OutputFixtures;

class BranchPushCommandTest extends BaseTestCase
{
    const TEST_USERNAME = 'cordoval';

    public function testCommand()
    {
        $processHelper = $this->expectProcessHelper();
        $gitHelper = $this->expectGitHelper();
        $this->expectsConfig();
        $tester = $this->getCommandTester($command = new BranchPushCommand());
        $command->getHelperSet()->set($processHelper, 'process');
        $command->getHelperSet()->set($gitHelper, 'git');

        $tester->execute(['--org' => 'cordoval', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(OutputFixtures::BRANCH_PUSH, trim($tester->getDisplay(true)));
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
                    'line' => 'git push -u cordoval some-branch',
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
            ->setMethods(['getBranchName'])
            ->getMock()
        ;
        $gitHelper
            ->expects($this->once())
            ->method('getBranchName')
            ->will($this->returnValue('some-branch'))
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
