<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\BranchDeleteCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class BranchDeleteCommandTest extends BaseTestCase
{
    const TEST_BRANCH_NAME = 'test_branch';

    public function testCommand()
    {
        $processHelper = $this->expectProcessHelper();
        $gitHelper = $this->expectGitHelper();
        $tester = $this->getCommandTester($command = new BranchDeleteCommand());
        $command->getHelperSet()->set($processHelper, 'process');
        $command->getHelperSet()->set($gitHelper, 'git');

        $tester->execute(['--org' => 'cordoval', '--repo' => 'gush']);

        $this->assertEquals(OutputFixtures::BRANCH_DELETE, trim($tester->getDisplay()));
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
                    'line' => 'git push -u origin :'.self::TEST_BRANCH_NAME,
                    'allow_failures' => true
                ],
            ])
        ;

        return $processHelper;
    }

    private function expectGitHelper()
    {
        $gitHelper = $this->getMock(
            'Gush\Helper\GitHelper',
            ['getBranchName']
        );
        $gitHelper->expects($this->once())
            ->method('getBranchName')
            ->will($this->returnValue(self::TEST_BRANCH_NAME))
        ;

        return $gitHelper;
    }
}
