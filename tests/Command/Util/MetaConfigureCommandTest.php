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

use Gush\Command\Util\MetaConfigureCommand;
use Gush\Tester\QuestionToken;
use Gush\Tests\Command\BaseTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Yaml\Yaml;

class MetaConfigureCommandTest extends BaseTestCase
{
    const META_HEADER = <<<OET
This file is part of Gush package.

(c) 2013-2014 Luis Cordova <cordoval@gmail.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
OET;

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
    }

    /**
     * @test
     */
    public function it_configures_the_meta_header()
    {
        $this->config->get('adapter')->willReturn('github_enterprise');
        $this->config->get('issue_tracker')->willReturn('github_enterprise');

        $expected = [
            'meta-header' => self::META_HEADER,
        ];

        $questionHelper = $this->expectDialogParameters(true);
        $template = $this->expectTemplate();

        $tester = $this->getCommandTester($command = new MetaConfigureCommand());
        $command->getHelperSet()->set($questionHelper);
        $command->getHelperSet()->set($template);

        $tester->execute(
            [
                'command' => 'meta:configure',
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
                    'Choose License:',
                    ['mit', 'gpl3', 'no-license']
                )
            )
        )->willReturn('mit');

        return $questionHelper->reveal();
    }

    private function expectTemplate()
    {
        $template = $this->prophet->prophesize('Gush\Helper\TemplateHelper');
        $template->setHelperSet(Argument::any())->shouldBeCalled();
        $template->getName()->willReturn('template');

        $template->askAndRender(
            Argument::any(),
            'meta-header',
            'mit'
        )->willReturn(self::META_HEADER);

        $template->getNamesForDomain('meta-header')->willReturn(['mit', 'gpl3', 'no-license']);
        $template->setInput(Argument::any())->shouldBeCalled();

        return $template->reveal();
    }
}
