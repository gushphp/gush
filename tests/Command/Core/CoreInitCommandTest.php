<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
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
    const PASSWORD = 'foo';
    const TOKEN = 'foo';
    const USERNAME = 'bar';
    const VERSIONEYE_TOKEN = 'token';
    const META_HEADER = "This file is part of Gush package.\n\n(c) 2013-2014 Luis Cordova <cordoval@gmail.com>\n\nThis source file is subject to the MIT license that is bundled\nwith this source code in the file LICENSE.";

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

    /**
     * @test
     */
    public function core_init_meta_option_set_to_true_puts_it_in_gush_yml_file()
    {
        $this->config->get('adapter')->willReturn('github_enterprise');
        $this->config->get('issue_tracker')->willReturn('github_enterprise');

        $expected = [
            'adapter' => 'github',
            'issue_tracker' => 'jira',
            'meta-header' => self::META_HEADER,
        ];

        $questionHelper = $this->expectDialogParameters(true);
        $template = $this->expectTemplate();

        $tester = $this->getCommandTester($command = new InitCommand());
        $command->getHelperSet()->set($questionHelper);
        $command->getHelperSet()->set($template);

        $tester->execute(
            [
                'command' => 'init',
                '--meta' => true,
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

    private function expectDialogParameters($withMeta = false)
    {
        $questionHelper = $this->prophet->prophesize('Symfony\Component\Console\Helper\QuestionHelper');

        $questionHelper->getName()->willReturn('question');
        $questionHelper->setHelperSet(Argument::any())->shouldBeCalled();

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

        if ($withMeta) {
            $questionHelper->ask(
                Argument::type('Symfony\Component\Console\Input\InputInterface'),
                Argument::type('Symfony\Component\Console\Output\OutputInterface'),
                new QuestionToken(
                    new ChoiceQuestion(
                        'Choose License:',
                        ['mit', 'gpl3', 'no-license']
                    )
                )
            )->willReturn('mit');
        }

        return $questionHelper->reveal();
    }

    private function expectTemplate()
    {
        $template = $this->prophet->prophesize('Gush\Helper\TemplateHelper');

        $template->askAndRender(
            Argument::any(),
            'meta-header',
            'mit'
        )->willReturn(self::META_HEADER);

        $template->getNamesForDomain('meta-header')->willReturn(['mit', 'gpl3', 'no-license']);

        $template->getName()->willReturn('template');
        $template->setHelperSet(Argument::any())->shouldBeCalled();
        $template->setInput(Argument::any())->shouldBeCalled();

        return $template->reveal();
    }
}
