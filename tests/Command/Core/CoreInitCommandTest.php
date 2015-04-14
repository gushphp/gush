<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\tests\Command\Core;

use Gush\Command\Core\InitCommand;
use Gush\Tester\QuestionToken;
use Gush\Tests\Command\BaseTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Yaml\Yaml;

class CoreInitCommandTest extends BaseTestCase
{
    const PASSWORD = 'foo';
    const TOKEN = 'foo';
    const USERNAME = 'bar';
    const VERSIONEYE_TOKEN = 'token';

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
        $this->config->has('[adapters][github]')->willReturn(true);
        $this->config->has('[adapters][github_enterprise]')->willReturn(true);
        $this->config->has('[issue_trackers][jira]')->willReturn(true);
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
                'command' => 'init',
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

    private function expectDialogParameters()
    {
        $questionHelper = $this->prophet->prophesize('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $questionHelper->getName()->willReturn('question');

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new ChoiceQuestion(
                    'Choose adapter:',
                    ['github', 'github_enterprise'],
                    'github_enterprise'
                )
            )
        )->willReturn('github');

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new ChoiceQuestion(
                    'Choose issue tracker:',
                    ['github', 'jira'],
                    'github_enterprise'
                )
            )
        )->willReturn('jira');

        return $questionHelper->reveal();
    }
}
