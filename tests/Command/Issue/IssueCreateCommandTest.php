<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Issue;

use Gush\Command\Issue\IssueCreateCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestAdapter;
use Symfony\Component\Console\Helper\HelperSet;

class IssueCreateCommandTest extends CommandTestCase
{
    const ISSUE_TITLE = 'bug title';
    const ISSUE_DESCRIPTION = 'not working!';

    public function testCreateIssueNonInteractive()
    {
        $command = new IssueCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getEditorHelper());
            }
        );

        $tester->execute(
            ['--title' => self::ISSUE_TITLE, '--body' => self::ISSUE_DESCRIPTION],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Created issue https://github.com/gushphp/gush/issues/77',
            $display
        );

        $issue = $command->getIssueTracker()->getIssue(TestAdapter::ISSUE_NUMBER_CREATED);

        $this->assertEquals(self::ISSUE_TITLE, $issue['title']);
        $this->assertEquals(self::ISSUE_DESCRIPTION, $issue['body']);
    }

    public function testCreateIssueInteractive()
    {
        $command = new IssueCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getEditorHelper());
            }
        );

        $this->setExpectedCommandInput($command, [self::ISSUE_TITLE, self::ISSUE_DESCRIPTION]);
        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Created issue https://github.com/gushphp/gush/issues/77',
            $display
        );

        $issue = $command->getIssueTracker()->getIssue(TestAdapter::ISSUE_NUMBER_CREATED);

        $this->assertEquals(self::ISSUE_TITLE, $issue['title']);
        $this->assertEquals(self::ISSUE_DESCRIPTION, $issue['body']);
    }

    public function testCreateIssueInteractiveWithExternalEditor()
    {
        $command = new IssueCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getEditorHelper(true));
            }
        );

        $this->setExpectedCommandInput($command, [self::ISSUE_TITLE, 'e']);
        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Created issue https://github.com/gushphp/gush/issues/77',
            $display
        );

        $issue = $command->getIssueTracker()->getIssue(TestAdapter::ISSUE_NUMBER_CREATED);

        $this->assertEquals(self::ISSUE_TITLE, $issue['title']);
        $this->assertEquals(self::ISSUE_DESCRIPTION, $issue['body']);
    }

    private function getEditorHelper($useEditor = false)
    {
        $editor = $this->getMockBuilder('Gush\Helper\EditorHelper')
            ->disableOriginalConstructor()
            ->setMethods(['fromString'])
            ->getMock()
        ;

        $editor->expects($useEditor ? $this->once() : $this->never())
            ->method('fromString')
            ->will($this->returnValue(self::ISSUE_DESCRIPTION))
        ;

        return $editor;
    }
}
