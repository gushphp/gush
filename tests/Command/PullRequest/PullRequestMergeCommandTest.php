<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestMergeCommand;
use Gush\Exception\UserException;
use Gush\Operation\RemoteMergeOperation;
use Gush\Tests\Command\CommandTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestMergeCommandTest extends CommandTestCase
{
    const MERGE_HASH = '8ae59958a2632018275b8db9590e9a79331030cb';

    const COMMAND_DISPLAY = <<<OET
This PR was merged into the base_ref branch.
OET;

    const COMMAND_DISPLAY_TARGET = <<<OET
Target: gushphp/base_ref
OET;

    const FAILURE_TYPE_DISPLAY = <<<OET
Pull-request type 'feat' is not accepted, choose of one of: security, feature, bug.
OET;

    const COMMAND_DISPLAY_SQUASHED = <<<OET
This PR was squashed before being merged into the base_ref branch (closes #10).
OET;

    const COMMAND_SWITCH_BASE = <<<OET
This PR was submitted for the base_ref branch but it was merged into the %s branch instead (closes #10).
OET;

    const COMMAND_SWITCH_BASE_TARGET = <<<OET
New-target: %s/%s (was "%s")
OET;

    const MERGE_NOTE_SWITCHED_BASE_AND_CLOSED = <<<OET
This PR was submitted for the `%s` branch but it was merged into the `%s` branch instead at @%s.
OET;

    const MERGE_NOTE_SQUASHED_AND_CLOSED = <<<OET
This PR was squashed before being merged into the `%s` branch at @%s.
OET;

    const MERGE_NOTE_SWITCHED_BASE_AND_SQUASHED_AND_CLOSED = <<<OET
This PR was submitted for the `%s` branch but it was squashed and merged into the `%s` branch instead at @%s.
OET;

    const COMMAND_DISPLAY_PAT_GIVEN = <<<OET
Pat given to @%s at https://github.com/gushphp/gush/issues/%u#issuecomment-2.
OET;

    const MERGE_NOTE_PAT_THANK_YOU = <<<OET
Thank you @%s.
OET;

    private $mergeMessage = <<<OET
%s #%d Write a behat test to launch strategy (cordoval)

This PR was merged into the base_ref branch.

Discussion
----------

Help me conquer the world. Teach them to use Gush.

Commits
-------

32fe234332fe234332fe234332fe234332fe2343 added merge pull request feature
ab34567812345678123456781234567812345678 added final touches
OET;

    private $mergeMessageSquash = <<<'OET'
%s #%d Write a behat test to launch strategy (cordoval)

This PR was squashed before being merged into the base_ref branch (closes #%2$d).

Discussion
----------

Help me conquer the world. Teach them to use Gush.

Commits
-------

32fe234332fe2343f2fea34332fe234332fe2343 added merge pull request feature
OET;

    private $mergeMessageSwitchBase = <<<'OET'
%s #%d Write a behat test to launch strategy (cordoval)

This PR was submitted for the base_ref branch but it was merged into the %s branch instead (closes #%2$d).

Discussion
----------

Help me conquer the world. Teach them to use Gush.

Commits
-------

32fe234332fe234332fe234332fe234332fe2343 added merge pull request feature
ab34567812345678123456781234567812345678 added final touches
OET;

    private $commits = [
        [
            'sha' => '32fe234332fe234332fe234332fe234332fe2343',
            'author' => 'Me <me@exm.com>',
            'subject' => 'added merge pull request feature',
            'message' => "added merge pull request feature\n.\nAnd some other cool stuff",
        ],
        [
            'sha' => 'ab34567812345678123456781234567812345678',
            'author' => 'Me <me@exm.com>',
            'subject' => 'added final touches',
            'message' => 'added final touches',
        ],
    ];

    private $squashedCommits = [
        [
            'sha' => '32fe234332fe2343f2fea34332fe234332fe2343',
            'author' => 'Me <me@exm.com>',
            'subject' => 'added merge pull request feature',
            'message' => "added merge pull request feature\n.\nAnd some other cool stuff",
        ],
    ];

    public function testMergePullRequest()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(sprintf($this->mergeMessage, 'merge', 10))->reveal());
            }
        );

        $tester->execute(
            ['pr_number' => 10],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches([self::COMMAND_DISPLAY, self::COMMAND_DISPLAY_TARGET], $display);
    }

    public function testMergePullRequestWithNoComments()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper(false)->reveal());
                $helperSet->set(
                    $this->getLocalGitHelper(
                        sprintf($this->mergeMessage, 'merge', 10),
                        false,
                        false,
                        null,
                        false
                    )->reveal()
                );
            }
        );

        $tester->execute(
            ['pr_number' => 10, '--no-comments' => true],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(self::COMMAND_DISPLAY, $display);
    }

    public function testMergePullRequestWithCustomType()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(sprintf($this->mergeMessage, 'feat', 10))->reveal());
            }
        );

        $tester->execute(
            ['pr_number' => 10, 'pr_type' => 'feat', '--pat' => 'none'],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(self::COMMAND_DISPLAY, $display);
    }

    public function testMergePullRequestTypeIsAsked()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(sprintf($this->mergeMessage, 'feature', 10))->reveal());
            }
        );

        $this->setExpectedCommandInput($command, "feature\n");

        $tester->execute(
            ['pr_number' => 10, '--pat' => 'none'],
            ['interactive' => true]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(self::COMMAND_DISPLAY, $display);
    }

    public function testMergePullRequestWithSquashOption()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set(
                    $this->getLocalGitHelper(sprintf($this->mergeMessageSquash, 'merge', 10), true)->reveal()
                );
            }
        );

        $tester->execute(
            ['pr_number' => 10, '--squash' => true, 'pr_type' => 'merge'],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(self::COMMAND_DISPLAY_SQUASHED, $display);
        $this->assertSame(sprintf(self::MERGE_NOTE_SQUASHED_AND_CLOSED, 'base_ref', self::MERGE_HASH), $command->getAdapter()->getComments(2)['body']);
    }

    public function testMergePullRequestWithForceSquashOption()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set(
                    $this->getLocalGitHelper(sprintf($this->mergeMessageSquash, 'merge', 10), true, true)->reveal()
                );
            }
        );

        $tester->execute(
            ['pr_number' => 10, '--force-squash' => true, 'pr_type' => 'merge'],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(self::COMMAND_DISPLAY_SQUASHED, $display);
        $this->assertSame(sprintf(self::MERGE_NOTE_SQUASHED_AND_CLOSED, 'base_ref', self::MERGE_HASH), $command->getAdapter()->getComments(2)['body']);
    }

    public function testMergePullRequestWithSwitchBase()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set(
                    $this->getLocalGitHelper(
                        sprintf($this->mergeMessageSwitchBase, 'merge', 10, 'develop'),
                        false,
                        false,
                        'develop'
                    )->reveal()
                );
            }
        );

        $tester->execute(
            ['pr_number' => 10, '--switch' => 'develop', 'pr_type' => 'merge'],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                sprintf(self::COMMAND_SWITCH_BASE_TARGET, 'gushphp', 'develop', 'base_ref'),
                sprintf(self::COMMAND_SWITCH_BASE, 'develop'),
            ],
            $display
        );
        $this->assertSame(sprintf(self::MERGE_NOTE_SWITCHED_BASE_AND_CLOSED, 'base_ref', 'develop', self::MERGE_HASH), $command->getAdapter()->getComments(2)['body']);
    }

    public function testMergePullRequestWithFastForward()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper(false)->reveal());
                $helperSet->set(
                    $this->getLocalGitHelper(
                        sprintf($this->mergeMessage, 'merge', 10, 'develop'),
                        false,
                        false,
                        null,
                        false,
                        true
                    )->reveal()
                );
            }
        );

        $tester->execute(
            ['pr_number' => 10, '--fast-forward' => true, 'pr_type' => 'merge'],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(sprintf(self::COMMAND_DISPLAY, 'develop'), $display);
    }

    public function testMergePullRequestInteractiveTypeAsk()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(CommandTestCase::$localConfig, ['pr_type' => ['security', 'feature', 'bug']]),
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(sprintf($this->mergeMessage, 'feature', 10))->reveal());
            }
        );

        $this->setExpectedCommandInput($command, "feature\n");
        $tester->execute(['pr_number' => 10, '--pat' => 'none']);

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(self::COMMAND_DISPLAY, $display);
    }

    public function testMergeTypeByArgumentIsValidatedWhenTypeRestrictionIsConfigured()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(CommandTestCase::$localConfig, ['pr_type' => ['security', 'feature', 'bug']]),
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper(false)->reveal());
                $helperSet->set($this->getLocalGitHelper(null, false, false, null, false)->reveal());
            }
        );

        $this->setExpectedCommandInput($command, "feature\n");

        $this->setExpectedException(
            UserException::class,
            "Pull-request type 'feat' is not accepted, choose of one of: security, feature, bug."
        );

        $tester->execute(
            ['pr_number' => 10, 'pr_type' => 'feat'],
            ['interactive' => false]
        );
    }

    public function testMergePullRequestWithOptionPat()
    {
        $prAuthor = 'weaverryan';
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(sprintf($this->mergeMessage, 'merge', 10))->reveal());
            }
        );

        $tester->execute(['pr_number' => 10, '--pat' => 'thank_you'], ['interactive' => false]);
        $this->assertCommandOutputMatches([
            self::COMMAND_DISPLAY, self::COMMAND_DISPLAY_TARGET, sprintf(self::COMMAND_DISPLAY_PAT_GIVEN, $prAuthor, 10),
        ], $tester->getDisplay());
        $this->assertSame(sprintf(self::MERGE_NOTE_PAT_THANK_YOU, $prAuthor), $command->getAdapter()->getComments(2)['body']);

        $tester->execute(['pr_number' => 10, '--pat' => 'random'], ['interactive' => false]);
        $this->assertCommandOutputMatches([
            self::COMMAND_DISPLAY, self::COMMAND_DISPLAY_TARGET, sprintf(self::COMMAND_DISPLAY_PAT_GIVEN, $prAuthor, 10),
        ], $tester->getDisplay());
        $this->assertContains('@'.$prAuthor, $command->getAdapter()->getComments(2)['body']);
    }

    public function testMergePullRequestWithOptionPatNone()
    {
        $prAuthor = 'weaverryan';
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(sprintf($this->mergeMessage, 'merge', 10))->reveal());
            }
        );

        $tester->execute(['pr_number' => 10, '--pat' => 'none'], ['interactive' => false]);
        $this->assertNotContains('Pat given to @', $tester->getDisplay());
        $prComments = $command->getAdapter()->getComments(2);
        $this->assertTrue(!isset($prComments['body']) || false === strpos($prComments['body'], '@'.$prAuthor));
    }

    protected function getGitConfigHelper($notes = true)
    {
        $helper = parent::getGitConfigHelper();

        $helper->ensureRemoteExists('gushphp', 'gush')->shouldBeCalled(); // base
        $helper->ensureRemoteExists('cordoval', 'gush')->shouldBeCalled(); // source

        if ($notes) {
            $helper->ensureNotesFetching('gushphp')->shouldBeCalled();
        }

        return $helper;
    }

    private function getLocalGitHelper($message = null, $squash = false, $forceSquash = false, $switch = null, $withComments = true, $fastForward = false)
    {
        $helper = parent::getGitHelper();

        if ($withComments) {
            $helper->remoteUpdate('gushphp')->shouldBeCalled();
            $helper->addNotes(Argument::any(), self::MERGE_HASH, 'github-comments')->shouldBeCalled();
            $helper->pushToRemote('gushphp', 'refs/notes/github-comments')->shouldBeCalled();
        }

        if (null !== $message) {
            $mergeOperation = $this->prophesize(RemoteMergeOperation::class);
            $mergeOperation->setTarget('gushphp', 'base_ref')->shouldBeCalled();
            $mergeOperation->setSource('cordoval', 'head_ref')->shouldBeCalled();
            $mergeOperation->squashCommits($squash, $forceSquash)->shouldBeCalled();
            $mergeOperation->switchBase($switch)->shouldBeCalled();
            $mergeOperation->useFastForward($fastForward)->shouldBeCalled();
            $mergeOperation->setMergeMessage(
                Argument::that(
                    function ($closure) use ($message, $switch) {
                        $closureMessage = trim($closure($switch ?: 'base_ref', 'temp--head_ref'));
                        $result = trim($message) === $closureMessage;

                        return $result;
                    }
                )
            )->shouldBeCalled();

            $mergeOperation->performMerge()->willReturn(self::MERGE_HASH);
            $mergeOperation->pushToRemote()->shouldBeCalled();

            $helper->createRemoteMergeOperation()->willReturn($mergeOperation->reveal());
            $helper->getLogBetweenCommits($switch ?: 'base_ref', 'temp--head_ref')->willReturn(
                $squash ? $this->squashedCommits : $this->commits
            );
        }

        return $helper;
    }
}
