<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestMergeCommand;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Gush\Tests\Command\BaseTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class PullRequestMergeCommandTest extends BaseTestCase
{
    /**
     * @var ObjectProphecy|GitHelper
     */
    private $git;

    /**
     * @var ObjectProphecy|GitConfigHelper
     */
    private $gitConfig;

    private $mergeHash = '8ae59958a2632018275b8db9590e9a79331030cb';

    private $mergeMessage = <<<OET
%s #%d Write a behat test to launch strategy (cordoval)

This PR was merged into the base_ref branch.

Discussion
----------

Help me conquer the world. Teach them to use gush.

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

Help me conquer the world. Teach them to use gush.

Commits
-------

32fe234332fe2343f2fea34332fe234332fe2343 added merge pull request feature
OET;

    const COMMAND_DISPLAY = <<<OET
[INFO] Adding remote 'gushphp' with 'git@github.com:gushphp/gush.git' to git local config.

[INFO] Adding remote 'cordoval' with 'git@github.com:cordoval/gush.git' to git local config.
This PR was merged into the base_ref branch.
OET;

    const FAILURE_TYPE_DISPLAY = <<<OET
[INFO] Adding remote 'gushphp' with 'git@github.com:gushphp/gush.git' to git local config.

[INFO] Adding remote 'cordoval' with 'git@github.com:cordoval/gush.git' to git local config.

[ERROR] Pull-request type 'feat' is not accepted, choose of one of: security, feature, bug.
OET;

    const COMMAND_DISPLAY_SQUASHED = <<<OET
[INFO] Adding remote 'gushphp' with 'git@github.com:gushphp/gush.git' to git local config.

[INFO] Adding remote 'cordoval' with 'git@github.com:cordoval/gush.git' to git local config.
This PR was squashed before being merged into the base_ref branch (closes #40).
OET;

    private $commits = [
        [
            'sha' => '32fe234332fe234332fe234332fe234332fe2343',
            'author' => 'Me <me@exm.com>',
            'subject' => 'added merge pull request feature',
            'message' => "added merge pull request feature\n.\nAnd some other cool stuff"
        ],
        [
            'sha' => 'ab34567812345678123456781234567812345678',
            'author' => 'Me <me@exm.com>',
            'subject' => 'added final touches',
            'message' => "added final touches"
        ],
    ];

    private $squashedCommits = [
        [
            'sha' => '32fe234332fe2343f2fea34332fe234332fe2343',
            'author' => 'Me <me@exm.com>',
            'subject' => 'added merge pull request feature',
            'message' => "added merge pull request feature\n.\nAnd some other cool stuff"
        ],
    ];

    /**
     * @test
     */
    public function merges_a_given_pull_request()
    {
        $message = sprintf($this->mergeMessage, 'merge', 40);

        $tester = $this->getTesterForCommand($message);
        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => 40, '--no-comments' => true],
            ['interactive' => false]
        );

        $this->assertEquals(self::COMMAND_DISPLAY, trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function merges_a_given_pull_request_with_custom_type()
    {
        $message = sprintf($this->mergeMessage, 'feat', 40);

        $tester = $this->getTesterForCommand($message);
        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => 40, 'pr_type' => 'feat', '--no-comments' => true],
            ['interactive' => false]
        );

        $this->assertEquals(self::COMMAND_DISPLAY, trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function squashes_and_merges_a_given_pull_request()
    {
        $message = sprintf($this->mergeMessageSquash, 'merge', 40);

        $tester = $this->getTesterForCommand($message, true);
        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', '--squash' => true, 'pr_number' => 40, '--no-comments' => true],
            ['interactive' => false]
        );

        $this->assertEquals(self::COMMAND_DISPLAY_SQUASHED, trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function asks_pr_type_when_not_given_by_argument()
    {
        $this->expectsConfig();
        $this->config->has('pr_type')->willReturn(true);
        $this->config->get('pr_type')->willReturn(['security', 'feature', 'bug']);

        $message = sprintf($this->mergeMessage, 'feature', 40);
        $tester = $this->getTesterForCommand($message, false, null, false, 'feature');

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => 40, '--no-comments' => true],
            ['interactive' => false]
        );

        $this->assertEquals(self::COMMAND_DISPLAY, trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function does_not_ask_pr_type_when_given_by_argument()
    {
        $this->expectsConfig();
        $this->config->has('pr_type')->willReturn(true);
        $this->config->get('pr_type')->willReturn(['security', 'feat', 'bug']);

        $message = sprintf($this->mergeMessage, 'feat', 40);
        $tester = $this->getTesterForCommand($message);

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => 40, 'pr_type' => 'feat', '--no-comments' => true],
            ['interactive' => false]
        );

        $this->assertEquals(self::COMMAND_DISPLAY, trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function errors_when_unaccepted_pr_type_is_provided_by_argument()
    {
        $this->expectsConfig();
        $this->config->has('pr_type')->willReturn(true);
        $this->config->get('pr_type')->willReturn(['security', 'feature', 'bug']);

        $tester = $this->getTesterForCommand(null);
        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => 40, 'pr_type' => 'feat', '--no-comments' => true],
            ['interactive' => false]
        );

        $this->assertEquals(self::FAILURE_TYPE_DISPLAY, trim($tester->getDisplay(true)));
    }

    /**
     * @param string|null $message
     * @param bool        $squash
     * @param string|null $switch
     * @param bool        $forceSquash
     * @param string|null $prType
     *
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    private function getTesterForCommand($message, $squash = false, $switch = null, $forceSquash = false, $prType = null)
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester($command);

        $application = $command->getApplication();

        $this->git = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $this->git->getName()->willReturn('git');
        $this->git->setHelperSet(Argument::any())->shouldBeCalled();

        if (null !== $message) {
            $mergeOperation = $this->prophet->prophesize('Gush\Operation\RemoteMergeOperation');
            $mergeOperation->setTarget('gushphp', 'base_ref')->shouldBeCalled();
            $mergeOperation->setSource('cordoval', 'head_ref')->shouldBeCalled();
            $mergeOperation->squashCommits($squash, $forceSquash)->shouldBeCalled();
            $mergeOperation->switchBase($switch)->shouldBeCalled();
            $mergeOperation->setMergeMessage(
                Argument::that(
                    function ($closure) use ($message, $switch) {
                        $closureMessage = trim($closure($switch ?: 'base_ref', 'temp--head_ref'));
                        $result = trim($message) === $closureMessage;

                        return $result;
                    }
                )
            )->shouldBeCalled();

            $mergeOperation->performMerge()->willReturn($this->mergeHash);
            $mergeOperation->pushToRemote()->shouldBeCalled();

            $this->git->createRemoteMergeOperation()->willReturn($mergeOperation->reveal());
            $this->git->getLogBetweenCommits($switch ?: 'base_ref', 'temp--head_ref')->willReturn(
                $squash ? $this->squashedCommits : $this->commits
            );
        }

        $this->gitConfig = $this->prophet->prophesize('Gush\Helper\GitConfigHelper');
        $this->gitConfig->getName()->willReturn('git_config');
        $this->gitConfig->setHelperSet(Argument::any())->shouldBeCalled();

        $this->gitConfig->remoteExists('cordoval', 'git@github.com:cordoval/gush.git')->willReturn(false);
        $this->gitConfig->remoteExists('gushphp', 'git@github.com:gushphp/gush.git')->willReturn(false);

        $this->gitConfig->setRemote(
            'cordoval',
            'git@github.com:cordoval/gush.git',
            'git@github.com:cordoval/gush.git'
        )->shouldBeCalled();

        $this->gitConfig->setRemote(
            'gushphp',
            'git@github.com:gushphp/gush.git',
            'git@github.com:gushphp/gush.git'
        )->shouldBeCalled();

        $helperSet = $application->getHelperSet();
        $helperSet->set($this->git->reveal());
        $helperSet->set($this->gitConfig->reveal());

        $questionHelper = $this->getMock(
            'Symfony\Component\Console\Helper\QuestionHelper',
            ['ask']
        );

        // Always do this expectation to prevent calling the real helper
        if (null !== $prType) {
            $questionHelper->expects($this->at(0))
                ->method('ask')
                ->will($this->returnValue($prType))
            ;
        } else {
            $questionHelper->expects($this->never())->method('ask');
        }

        $helperSet->set($questionHelper);

        return $tester;
    }
}
