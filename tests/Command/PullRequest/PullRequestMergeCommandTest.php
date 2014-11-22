<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestMergeCommand;
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

    private static $mergeHash = '8ae59958a2632018275b8db9590e9a79331030cb';

    private static $message = <<<OET
%s #%d Write a behat test to launch strategy (weaverryan)

This PR was merged into the base_ref branch.

Discussion
----------

Help me conquer the world. Teach them to use gush.

Commits
-------

32fe234332fe234332fe234332fe234332fe2343 added merge pull request feature (cordoval)
ab34567812345678123456781234567812345678 added final touches (cordoval)
OET;

    /**
     * @test
     */
    public function merges_a_given_pull_request()
    {
        $message = sprintf(self::$message, 'merge', 40);

        $stringComparison = function ($value) use ($message) {
            return 0 === strpos($value, $message);
        };

        $tester = $this->getTesterForCommand();
        $this->git->mergeRemoteBranch(
            'cordoval',
            'origin',
            'base_ref',
            'head_ref',
            Argument::that($stringComparison)
        )->willReturn(self::$mergeHash);

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => 40, '--no-comments' => true],
            ['interactive' => false]
        );

        $this->assertEquals('Pull Request successfully merged.', trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function merges_a_given_pull_request_with_custom_type()
    {
        $message = sprintf(self::$message, 'feat', 40);

        $stringComparison = function ($value) use ($message) {
            return 0 === strpos($value, $message);
        };

        $tester = $this->getTesterForCommand();
        $this->git->mergeRemoteBranch(
            'cordoval',
            'origin',
            'base_ref',
            'head_ref',
            Argument::that($stringComparison)
        )->willReturn(self::$mergeHash);

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => 40, 'pr_type' => 'feat', '--no-comments' => true],
            ['interactive' => false]
        );

        $this->assertEquals('Pull Request successfully merged.', trim($tester->getDisplay(true)));
    }

    /**
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    private function getTesterForCommand()
    {
        $command = new PullRequestMergeCommand();
        $tester = $this->getCommandTester($command);

        $application = $command->getApplication();

        $this->git = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $this->git->getName()->willReturn('git');
        $this->git->setHelperSet(Argument::any())->willReturn(null);

        $helperSet = $application->getHelperSet();
        $helperSet->set($this->git->reveal());

        return $tester;
    }
}
