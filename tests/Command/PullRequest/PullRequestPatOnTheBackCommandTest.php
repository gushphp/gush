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
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestPatOnTheBackCommandTest extends CommandTestCase
{
    const TEMPLATE_STRING = 'Good catch @weaverryan, thanks for the patch.';

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

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Pat on the back pushed to https://github.com/gushphp/gush/pull/10',
            $display
        );
    }

    private function getTemplateHelper()
    {
        $template = $this->getMockBuilder('Gush\Helper\TemplateHelper')
            ->disableOriginalConstructor()
            ->setMethods(['bindAndRender'])
            ->getMock()
        ;

        $template->expects($this->once())
            ->method('bindAndRender')
            ->with(['author' => 'weaverryan', 'pat' => 'good_job'], 'pats', 'general')
            ->will($this->returnValue(self::TEMPLATE_STRING))
        ;

        return $template;
    }
}
