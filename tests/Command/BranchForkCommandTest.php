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

use Gush\Command\BranchForkCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class BranchForkCommandTest extends BaseTestCase
{
    const TEST_USERNAME = 'cordoval';

    public function testCommand()
    {
        $processHelper = $this->expectProcessHelper();
        $this->expectsConfig();
        $tester = $this->getCommandTester($command = new BranchForkCommand());
        $command->getHelperSet()->set($processHelper, 'process');

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(OutputFixtures::BRANCH_FORK, trim($tester->getDisplay()));
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
                    'line' => 'git remote add cordoval git@github.com:cordoval/gush.git',
                    'allow_failures' => true
                ],
            ])
        ;

        return $processHelper;
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
