<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Core;

use Gush\Command\Core\InitCommand;
use Gush\Tester\QuestionToken;
use Gush\Tests\Command\BaseTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Yaml\Yaml;

class CoreInitCommandTest extends BaseTestCase
{
    const USERNAME = 'bar';
    const PASSWORD = 'foo';
    const TOKEN = 'foo';

    private $gushLocalFilename;

    protected function setUp()
    {
        parent::setUp();

        if (!$homeDir = getenv('GUSH_HOME')) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' in your \'phpunit.xml\'.');
        }

        $localDir = $homeDir.'/local_test';
        $this->gushLocalFilename = $localDir.'/.gush.yml';

        @mkdir($localDir, 0777, true);

        if (file_exists($this->gushLocalFilename)) {
            unlink($this->gushLocalFilename);
        }

        $this->config->get('local_config')->willReturn($this->gushLocalFilename);
        $this->config->has('[adapters][github]')->willReturn(false);
        $this->config->has('[issue_trackers][jira]')->willReturn(false);
    }

    /**
     * @test
     */
    public function accepts_adapter_and_issue_tracker_from_input()
    {
        $expected = [
            'adapter' => 'github',
            'issue_tracker' => 'jira',
        ];

        $questionHelper = $this->expectDialogParameters(false);
        $tester = $this->getCommandTester($command = new InitCommand());
        $command->getHelperSet()->set($questionHelper);

        $tester->execute(
            [
                'command' => $command->getName(),
                '--adapter' => 'github',
                '--issue-tracker' => 'jira',
            ],
            [
                'interactive' => false,
            ]
        );

        $this->assertGushLocalEquals($expected);
    }

    /**
     * @test
     */
    public function core_init_writes_local_gush_file()
    {
        $this->config->get('adapter')->willReturn('github_enterprise');
        $this->config->get('issue_tracker')->willReturn('github_enterprise');

        $expected = [
            'adapter' => 'github',
            'issue_tracker' => 'jira',
        ];

        $questionHelper = $this->expectDialogParameters();
        $tester = $this->getCommandTester($command = new InitCommand());
        $command->getHelperSet()->set($questionHelper);

        $tester->execute(
            [
                'command' => $command->getName(),
            ],
            [
                'interactive' => true,
            ]
        );

        $this->assertGushLocalEquals($expected);
    }

    private function assertGushLocalEquals(array $expected)
    {
        $this->assertFileExists($this->gushLocalFilename);
        $this->assertEquals($expected, Yaml::parse(file_get_contents($this->gushLocalFilename)));
    }

    private function expectDialogParameters($interactive = true)
    {
        $styleHelper = $this->prophet->prophesize('Gush\Helper\StyleHelper');
        $styleHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $styleHelper->setInput(Argument::any())->shouldBeCalled();
        $styleHelper->setOutput(Argument::any())->shouldBeCalled();
        $styleHelper->getName()->willReturn('gush_style');

        // Common styling, no need to test
        $styleHelper->note(Argument::any())->shouldBeCalled();
        $styleHelper->success(Argument::any())->shouldBeCalled();

        if ($interactive) {
            $styleHelper->numberedChoice('Choose repository-manager', Argument::any())->willReturn('github');
            $styleHelper->numberedChoice('Choose issue-tracker', Argument::any())->willReturn('jira');

            $styleHelper->confirm(
                'Would you like to configure the missing adapters now?',
                Argument::any()
            )->willReturn(false);
        }

        return $styleHelper->reveal();
    }
}
