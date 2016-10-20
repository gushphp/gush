<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Branch;

use Gush\Command\Branch\BranchChangelogCommand;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class BranchChangelogCommandTest extends CommandTestCase
{
    const TEST_TAG_NAME = '1.2.3';

    protected function requiresRealConfigDir()
    {
        return true;
    }

    public function testFailsWhenNoTagsAreFoundOnBranch()
    {
        $command = new BranchChangelogCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(true, false)->reveal());
            }
        );

        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches('No tags were found on branch "master"', $display);
    }

    public function testShowChangelogWhenTagIsFoundForBranch()
    {
        $command = new BranchChangelogCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper()->reveal());
            }
        );

        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            '* Write a behat test to launch strategy ([#123](https://github.com/gushphp/gush/issues/123))',
            $display
        );
    }

    public function testShowChangelogInCustomFormat()
    {
        $command = new BranchChangelogCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(
                self::$localConfig,
                [
                    'templates' => [
                        'changelog' => '{% for item in items %}* {{ item.title }} ([{{ item.id }}]({{ item.url }})) by {{ item.user }}<br>{% endfor %}',
                    ],
                ]
            ),
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper()->reveal());
            }
        );

        $tester->execute(['--search' => ['/#(?P<id>[0-9]+)/i', '/GITHUB-(?P<id>[0-9]+)/i']]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                '* Write a behat test to launch strategy ([#123](https://github.com/gushphp/gush/issues/123)) by weaverryan<br>',
                '* Write a behat test to launch strategy ([GITHUB-500](https://github.com/gushphp/gush/issues/500)) by weaverryan<br>',
            ],
            $display
        );

    }

    public function testSearchWithIssuePattern()
    {
        $command = new BranchChangelogCommand();
        $tester = $this->getCommandTester($command);

        $tester->execute(['--search' => ['/#(?P<id>[0-9]+)/i', '/GITHUB-(?P<id>[0-9]+)/i']]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                '* Write a behat test to launch strategy ([#123](https://github.com/gushphp/gush/issues/123))',
                '* Write a behat test to launch strategy ([GITHUB-500](https://github.com/gushphp/gush/issues/500))',
            ],
            $display
        );
    }

    protected function getGitHelper($isGitDir = true, $hasTag = true)
    {
        $helper = parent::getGitHelper($isGitDir);
        $helper->getActiveBranchName()->willReturn('master');

        if ($hasTag) {
            $helper->getLastTagOnBranch('master')->willReturn(self::TEST_TAG_NAME);
            $helper->getLogBetweenCommits(self::TEST_TAG_NAME, 'master')->willReturn(
                [
                    [
                        'sha' => '68bfa1d00',
                        'author' => 'Anonymous <someone@example.com>',
                        'subject' => ' Another hack which fixes #123',
                        'message' => ' Another hack which fixes #123',
                    ],
                    [
                        'sha' => '68bfa1d05',
                        'author' => 'Anonymous <someone@example.com>',
                        'subject' => ' Another hack which fixes GITHUB-500',
                        'message' => ' Another hack which fixes GITHUB-500',
                    ],
                ]
            );
        } else {
            $helper->getLastTagOnBranch()->willThrow(
                new \RuntimeException('fatal: No names found, cannot describe anything.')
            );
        }

        return $helper;
    }
}
