<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Issue;

use Gush\Command\Issue\IssueTakeCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestAdapter;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;

class IssueTakeCommandTest extends CommandTestCase
{
    const SLUGIFIED_STRING = 'write-a-behat-test-to-launch-strategy';
    const TEST_TITLE = 'Write a behat test to launch strategy';

    public function testTakeIssue()
    {
        $command = new IssueTakeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->expectTextHelper()->reveal());
                $helperSet->set($this->getGitConfigHelper()->reveal());
                $helperSet->set($this->getLocalGitHelper()->reveal());
            }
        );

        $tester->execute(['issue_number' => TestAdapter::ISSUE_NUMBER]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            sprintf('Issue https://github.com/gushphp/gush/issues/%s taken', TestAdapter::ISSUE_NUMBER),
            $display
        );
    }

    public function testTakeIssueWithSpecificBase()
    {
        $command = new IssueTakeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->expectTextHelper()->reveal());
                $helperSet->set($this->getGitConfigHelper()->reveal());
                $helperSet->set($this->getLocalGitHelper('gushphp', 'gushphp/development')->reveal());
            }
        );

        $tester->execute(['issue_number' => TestAdapter::ISSUE_NUMBER, 'base_branch' => 'development']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            sprintf('Issue https://github.com/gushphp/gush/issues/%s taken', TestAdapter::ISSUE_NUMBER),
            $display
        );
    }

    public function testTakeIssueWithSpecificBaseFromConfig()
    {
        $command = new IssueTakeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(self::$localConfig, ['base' => 'development']),
            function (HelperSet $helperSet) {
                $helperSet->set($this->expectTextHelper()->reveal());
                $helperSet->set($this->getGitConfigHelper()->reveal());
                $helperSet->set($this->getLocalGitHelper('gushphp', 'gushphp/development')->reveal());
            }
        );

        $tester->execute(['issue_number' => TestAdapter::ISSUE_NUMBER]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            sprintf('Issue https://github.com/gushphp/gush/issues/%s taken', TestAdapter::ISSUE_NUMBER),
            $display
        );
    }

    public function testTakeIssueFromSpecificOrgAndRepo()
    {
        $command = new IssueTakeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->expectTextHelper()->reveal());
                $helperSet->set($this->getGitConfigHelper('gushphp-fork', 'gush-source')->reveal());
                $helperSet->set($this->getLocalGitHelper('gushphp-fork', 'gushphp-fork/master')->reveal());
            }
        );

        $tester->execute(
            [
                '--org' => 'gushphp-fork',
                '--repo' => 'gush-source',
                '--issue-org' => 'gushphp',
                '--issue-project' => 'gush',
                'issue_number' => TestAdapter::ISSUE_NUMBER,
            ]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            sprintf('Issue https://github.com/gushphp/gush/issues/%s taken', TestAdapter::ISSUE_NUMBER),
            $display
        );
    }

    private function expectTextHelper()
    {
        $text = $this->prophesize('Gush\Helper\TextHelper');
        $text->setHelperSet(Argument::any())->shouldBeCalled();
        $text->getName()->willReturn('text');

        $text->slugify(
            sprintf('%d %s', TestAdapter::ISSUE_NUMBER, self::TEST_TITLE)
        )->willReturn(self::SLUGIFIED_STRING);

        return $text;
    }

    private function getLocalGitHelper($remote = 'gushphp', $baseBranch = 'gushphp/master')
    {
        $gitHelper = $this->getGitHelper();
        $gitHelper->remoteUpdate($remote)->shouldBeCalled();
        $gitHelper->checkout($baseBranch)->shouldBeCalled();
        $gitHelper->checkout(self::SLUGIFIED_STRING, true)->shouldBeCalled();

        return $gitHelper;
    }

    protected function getGitConfigHelper($org = 'gushphp', $repo = 'gush')
    {
        $helper = parent::getGitConfigHelper();
        $helper->ensureRemoteExists($org, $repo)->shouldBeCalled();

        return $helper;
    }
}
