<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestPatOnTheBackCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class PullRequestPatOnTheBackCommandTest extends BaseTestCase
{
    const TEMPLATE_STRING = "Good catch @weaverryan, thanks for the patch.";

    /**
     * @test
     */
    public function pats_on_the_back_contributor_of_a_pull_request()
    {
        $template = $this->expectTemplateHelper();
        $tester = $this->getCommandTester($command = new PullRequestPatOnTheBackCommand());
        $command->getHelperSet()->set($template, 'template');
        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => TestAdapter::PULL_REQUEST_NUMBER],
            ['interactive' => false]
        );

        $this->assertEquals(OutputFixtures::PULL_REQUEST_PAT_ON_THE_BACK, trim($tester->getDisplay(true)));
    }

    private function expectTemplateHelper()
    {
        $template = $this->getMockBuilder('Gush\Helper\TemplateHelper')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $template->expects($this->once())
            ->method('bindAndRender')
            ->with(['author' => 'weaverryan'], 'pats', 'general')
            ->will($this->returnValue(self::TEMPLATE_STRING))
        ;

        return $template;
    }
}
