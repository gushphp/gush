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

use Gush\Command\PullRequest\PullRequestPatOnTheBackCommand;
use Gush\Helper\TemplateHelper;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestPatOnTheBackCommandTest extends CommandTestCase
{
    private static $pats = [
        'good_job' => 'Good catch @weaverryan, thanks for the patch.',
        'thanks' => 'Thanks @weaverryan.',
        'beers' => ':beers: @weaverryan.',
    ];

    public function testPatContributorOfPullRequestOnTheBack()
    {
        $tester = $this->getCommandTester(
            new PullRequestPatOnTheBackCommand(),
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getTemplateHelper());
            }
        );

        $tester->execute(['pr_number' => 10], ['interactive' => false]);

        $this->assertCommandOutputMatches(
            'Pat on the back pushed to https://github.com/gushphp/gush/pull/10',
            $tester->getDisplay()
        );
    }

    public function testPatOnTheBackWithOption()
    {
        $tester = $this->getCommandTester(
            new PullRequestPatOnTheBackCommand(),
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getTemplateHelper('thanks'));
            }
        );

        $tester->execute(['pr_number' => 10, '--pat' => 'thanks'], ['interactive' => false]);

        $this->assertCommandOutputMatches(
            'Pat on the back pushed to https://github.com/gushphp/gush/pull/10',
            $tester->getDisplay()
        );
    }

    public function testChoosePatOnTheBack()
    {
        $command = new PullRequestPatOnTheBackCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getTemplateHelper('beers'));
            }
        );

        $this->setExpectedCommandInput($command, ['beers']);

        $tester->execute(['pr_number' => 10]);

        $this->assertCommandOutputMatches(
            'Pat on the back pushed to https://github.com/gushphp/gush/pull/10',
            $tester->getDisplay()
        );
    }

    public function testPatOnTheBackWithRandomOption()
    {
        $tester = $this->getCommandTester(new PullRequestPatOnTheBackCommand());

        $tester->execute(['pr_number' => 10, '--pat' => 'random'], ['interactive' => false]);

        $this->assertCommandOutputMatches(
            'Pat on the back pushed to https://github.com/gushphp/gush/pull/10',
            $tester->getDisplay()
        );
    }

    private function getTemplateHelper($pat = 'good_job')
    {
        $template = $this->getMockBuilder(TemplateHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['bindAndRender'])
            ->getMock()
        ;

        $template->expects($this->once())
            ->method('bindAndRender')
            ->with(['author' => 'weaverryan', 'pat' => $pat], 'pats', 'general')
            ->will($this->returnValue(self::$pats[$pat]))
        ;

        return $template;
    }
}
