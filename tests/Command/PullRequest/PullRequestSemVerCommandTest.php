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

use Gush\Command\PullRequest\PullRequestSemVerCommand;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestSemVerCommandTest extends CommandTestCase
{
    /**
     * @dataProvider getSemVersions
     *
     * @param string $optionName
     * @param string $tag
     * @param string $expectedResult
     */
    public function testGetSemverFor($optionName, $tag, $expectedResult)
    {
        $command = new PullRequestSemVerCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) use ($tag) {
                $helperSet->set($this->getGitHelper(true, $tag)->reveal());
            }
        );

        $tester->execute(['pr_number' => 10, '--'.$optionName => true]);

        $this->assertCommandOutputMatches(preg_quote($expectedResult, '#'), $tester->getDisplay(), true);
    }

    public static function getSemVersions()
    {
        return [
            ['major', 'v1.0.0', '2.0.0'],
            ['major', 'v1.0.5', '2.0.0'],
            ['major', '1.0.0', '2.0.0'],
            ['minor', '1.0.0', '1.1.0'],
            ['patch', '1.0.0', '1.0.1'],
            // alpha
            ['minor', '0.0.0', '0.1.0'],
            ['major', '0.0.0', '1.0.0'],
        ];
    }

    protected function getGitConfigHelper()
    {
        $helper = parent::getGitConfigHelper();
        $helper->ensureRemoteExists('cordoval', 'gush')->shouldBeCalled();

        return $helper;
    }

    protected function getGitHelper($isGitDir = true, $tag = 'v1.0.0')
    {
        $helper = parent::getGitHelper($isGitDir);
        $helper->remoteUpdate('cordoval')->shouldBeCalled();
        $helper->getLastTagOnBranch('cordoval/head_ref')->willReturn($tag);

        return $helper;
    }
}
