<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\PullRequest\PullRequestFixerCommand;
use Gush\Tests\Fixtures\OutputFixtures;

class PullRequestFixerCommandTest extends BaseTestCase
{
    const TEST_BRANCH_NAME = 'test_branch';

    public function testCommand()
    {
        $processHelper = $this->expectProcessHelper();
        $tester = $this->getCommandTester($command = new PullRequestFixerCommand());
        $command->getHelperSet()->set($processHelper, 'process');

        $tester->execute([]);

        $this->assertEquals(OutputFixtures::PULL_REQUEST_FIXER, trim($tester->getDisplay(true)));
    }

    private function expectProcessHelper()
    {
        $processHelper = $this->getMock(
            'Gush\Helper\ProcessHelper',
            ['runCommands', 'probePhpCsFixer']
        );
        $processHelper->expects($this->once())
            ->method('probePhpCsFixer')
        ;
        $processHelper->expects($this->once())
            ->method('runCommands')
            ->with(
                [
                    [
                        'line' => 'git add .',
                        'allow_failures' => true
                    ],
                    [
                        'line' => 'git commit -am wip',
                        'allow_failures' => true
                    ],
                    [
                        'line' => 'php-cs-fixer fix .',
                        'allow_failures' => true
                    ],
                    [
                        'line' => 'git add .',
                        'allow_failures' => true
                    ],
                    [
                        'line' => 'git commit -am cs-fixer',
                        'allow_failures' => true
                    ]
                ]
            )
        ;

        return $processHelper;
    }
}
