<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\tests\Command\Branch;

use Gush\Command\Branch\BranchChangelogCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;

class BranchChangelogCommandTest extends BaseTestCase
{
    const TEST_TAG_NAME = '1.2.3';

    /**
     * @test
     */
    public function expects_an_exception_when_no_tags_on_branch()
    {
        $tester = $this->getCommandTester($command = new BranchChangelogCommand());
        $command->getHelperSet()->set($this->expectGitHelperWithoutTags());

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(OutputFixtures::BRANCH_CHANGELOG_EMPTY, trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function finds_tag_on_branch_to_build_changelog()
    {
        $tester = $this->getCommandTester($command = new BranchChangelogCommand());
        $command->getHelperSet()->set($this->expectGitHelperWithTags());

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush']);

        $this->assertEquals(OutputFixtures::BRANCH_CHANGELOG, trim($tester->getDisplay(true)));
    }

    private function expectGitHelperWithoutTags()
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->getName()->willReturn('git');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();

        $gitHelper->getLastTagOnBranch()->willThrow(
            new \RuntimeException('fatal: No names found, cannot describe anything.')
        );

        return $gitHelper->reveal();
    }

    private function expectGitHelperWithTags()
    {
        $commits = [
            [
                'sha' => '68bfa1d00',
                'author' => 'Anonymous <someone@example.com>',
                'subject' => ' Another hack which fixes #123',
                'message' => ' Another hack which fixes #123'
            ]
        ];

        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->getName()->willReturn('git');

        $gitHelper->getLastTagOnBranch()->willReturn(self::TEST_TAG_NAME);
        $gitHelper->getLogBetweenCommits(self::TEST_TAG_NAME, 'HEAD')->willReturn($commits);
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();

        return $gitHelper->reveal();
    }
}
