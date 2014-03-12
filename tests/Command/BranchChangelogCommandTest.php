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

use Gush\Command\BranchChangelogCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Anton Babenko <anton@antonbabenko.com>
 */
class BranchChangelogCommandTest extends BaseTestCase
{
    const TEST_TAG_NAME = '1.2.3';

    public function testCommandForRepositoriesWithoutTags()
    {
        $gitHelperWithoutTags = $this->expectGitHelperWithoutTags();

        $tester = $this->getCommandTester($command = new BranchChangelogCommand());
        $command->getHelperSet()->set($gitHelperWithoutTags, 'git');

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush']);

        $this->assertEquals(OutputFixtures::BRANCH_CHANGELOG_EMPTY, trim($tester->getDisplay()));
    }

    public function testCommandForRepositoriesWithTags()
    {
        $gitHelperWithTags = $this->expectGitHelperWithTags();

        $tester = $this->getCommandTester($command = new BranchChangelogCommand());
        $command->getHelperSet()->set($gitHelperWithTags, 'git');

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush']);

        $this->assertEquals(OutputFixtures::BRANCH_CHANGELOG, trim($tester->getDisplay()));
    }

    private function expectGitHelperWithoutTags()
    {
        $gitHelper = $this->getMock(
            'Gush\Helper\GitHelper',
            ['runGitCommand']
        );
        $gitHelper->expects($this->any())
            ->method('runGitCommand')
            ->with('git describe --abbrev=0 --tags')
            ->will($this->throwException(new \RuntimeException()))
        ;

        return $gitHelper;
    }

    private function expectGitHelperWithTags()
    {
        $gitHelper = $this->getMock(
            'Gush\Helper\GitHelper',
            ['runGitCommand']
        );
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
