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

use Gush\Command\IssueCreateCommand;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueCreateCommandTest extends BaseTestCase
{
    const ISSUE_TITLE = 'bug title';
    const ISSUE_DESCRIPTION = 'not working!';

    public function testCommand()
    {
        $dialog = $this->expectDialogParameters();
        $tester = $this->getCommandTester($command = new IssueCreateCommand());
        $command->getHelperSet()->set($dialog, 'dialog');
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush']);

        $this->assertEquals('Created issue https://github.com/gushphp/gush/issues/77', trim($tester->getDisplay()));
    }

    private function expectDialogParameters()
    {
        $dialog = $this->getMock(
            'Symfony\Component\Console\Helper\DialogHelper',
            ['askAndValidate']
        );
        $dialog->expects($this->at(0))
            ->method('askAndValidate')
            ->will($this->returnValue(self::ISSUE_TITLE));
        $dialog->expects($this->at(1))
            ->method('askAndValidate')
            ->will($this->returnValue(self::ISSUE_DESCRIPTION));

        return $dialog;
    }
}
