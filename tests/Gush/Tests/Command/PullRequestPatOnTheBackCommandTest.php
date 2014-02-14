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

use Gush\Command\PullRequestPatOnTheBackCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestPatOnTheBackCommandTest extends BaseTestCase
{
    const TEMPLATE_STRING = "Good catch @weaverryan, thanks for the patch.";

    public function testCommand()
    {
        $template = $this->expectTemplateHelper();
        $tester = $this->getCommandTester($command = new PullRequestPatOnTheBackCommand());
        $command->getHelperSet()->set($template, 'template');
        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'pr_number' => TestAdapter::PULL_REQUEST_NUMBER]
        );

        $this->assertEquals(OutputFixtures::PULL_REQUEST_PAT_ON_THE_BACK, trim($tester->getDisplay()));
    }

    private function expectTemplateHelper()
    {
        $template = $this->getMockBuilder('Gush\Helper\TemplateHelper')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $template->expects($this->once())
            ->method('bindAndRender')
            ->with(['author' => 'weaverryan'], 'pats/general', 'pats')
            ->will($this->returnValue(self::TEMPLATE_STRING))
        ;

        return $template;
    }
}
