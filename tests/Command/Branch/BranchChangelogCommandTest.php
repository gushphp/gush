<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Branch;

use Gush\Command\Branch\BranchChangelogCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class BranchChangelogCommandTest extends BaseTestCase
{
    const TEST_TAG_NAME = '1.2.3';

    /**
     * @test
     */
    public function expects_an_exception_when_no_tags_on_branch()
    {
        $gitHelperWithoutTags = $this->expectGitHelperWithoutTags();

        $tester = $this->getCommandTester($command = new BranchChangelogCommand());
        $command->getHelperSet()->set($gitHelperWithoutTags, 'git');

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(OutputFixtures::BRANCH_CHANGELOG_EMPTY, trim($tester->getDisplay(true)));
    }

    /**
     * @test
     */
    public function finds_tag_on_branch_to_build_changelog()
    {
        $gitHelperWithTags = $this->expectGitHelperWithTags();

        $tester = $this->getCommandTester($command = new BranchChangelogCommand());
        $command->getHelperSet()->set($gitHelperWithTags, 'git');

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush']);

        $this->assertEquals(OutputFixtures::BRANCH_CHANGELOG, trim($tester->getDisplay(true)));
    }

    private function expectGitHelperWithoutTags()
    {
        $gitHelper = $this
            ->getMockBuilder('Gush\Helper\GitHelper')
            ->disableOriginalConstructor()
            ->setMethods(['runGitCommand'])
            ->getMock()
        ;
        $gitHelper->expects($this->any())
            ->method('runGitCommand')
            ->with('git describe --abbrev=0 --tags')
            ->will($this->throwException(new \RuntimeException()))
        ;

        return $gitHelper;
    }

    private function expectGitHelperWithTags()
    {
        $gitHelper = $this
            ->getMockBuilder('Gush\Helper\GitHelper')
            ->disableOriginalConstructor()
            ->setMethods(['runGitCommand'])
            ->getMock()
        ;
        $gitHelper->expects($this->any())
            ->method('runGitCommand')
            ->will(
                $this->returnValueMap(
                    [
                        ['git describe --abbrev=0 --tags', self::TEST_TAG_NAME],
                        [sprintf('git log %s...HEAD --oneline', self::TEST_TAG_NAME), 'Another hack which fixes #123']
                    ]
                )
            )
        ;

        return $gitHelper;
    }
}
