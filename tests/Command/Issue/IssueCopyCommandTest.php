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

use Gush\Command\Issue\IssueCopyCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestAdapter;

class IssueCopyCommandTest extends CommandTestCase
{
    public function testCopyIssueFromOrganizationToTargetOrganization()
    {
        $tester = $this->getCommandTester($command = new IssueCopyCommand());
        $tester->execute(
            [
                'issue_number' => TestAdapter::ISSUE_NUMBER,
                'target_username' => 'dantleech',
                'target_repository' => 'gushphp',
            ]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Opened issue: https://github.com/gushphp/gush/issues/77',
            $display
        );

        $issue = $command->getIssueTracker()->getIssue(TestAdapter::ISSUE_NUMBER_CREATED);

        $this->assertEquals('Write a behat test to launch strategy', $issue['title']);
        $this->assertEquals('Help me conquer the world. Teach them to use Gush.', $issue['body']);
        $this->assertEquals('dantleech', $issue['org']);
        $this->assertEquals('gushphp', $issue['repo']);

        // Ensure the original org/repo is restored
        $this->assertEquals('gushphp', $command->getIssueTracker()->getUsername());
        $this->assertEquals('gush', $command->getIssueTracker()->getRepository());
    }

    public function testCopyIssueFromOrganizationToTargetOrganizationWithTitlePrefix()
    {
        $tester = $this->getCommandTester($command = new IssueCopyCommand());
        $tester->execute(
            [
                'issue_number' => TestAdapter::ISSUE_NUMBER,
                'target_username' => 'dantleech',
                'target_repository' => 'gushphp',
                '--prefix' => '[SomePrefix] ',
                '--close' => true,
            ]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Closed issue: https://github.com/gushphp/gush/issues/7',
            $display
        );

        $issue = $command->getIssueTracker()->getIssue(TestAdapter::ISSUE_NUMBER_CREATED);

        $this->assertEquals('[SomePrefix] Write a behat test to launch strategy', $issue['title']);
        $this->assertEquals('Help me conquer the world. Teach them to use Gush.', $issue['body']);
        $this->assertEquals('dantleech', $issue['org']);
        $this->assertEquals('gushphp', $issue['repo']);
    }

    public function testCopyIssueFromOrganizationToTargetOrganizationCloseOld()
    {
        $tester = $this->getCommandTester($command = new IssueCopyCommand());
        $tester->execute(
            [
                'issue_number' => TestAdapter::ISSUE_NUMBER,
                'target_username' => 'dantleech',
                'target_repository' => 'gushphp',
                '--close' => true,
            ]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Opened issue: https://github.com/gushphp/gush/issues/77',
                'Closed issue: https://github.com/gushphp/gush/issues/7',
            ],
            $display
        );
    }
}
