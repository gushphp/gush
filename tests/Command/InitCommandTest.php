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

use Gush\Command\Core\InitCommand;
use Prophecy\Argument;
use Symfony\Component\Yaml\Yaml;

class InitCommandTest extends BaseTestCase
{
    const PASSWORD = 'foo';
    const TOKEN = 'foo';
    const USERNAME = 'bar';
    const VERSIONEYE_TOKEN = 'token';
    const META_HEADER = "This file is part of Gush package.\n\n(c) 2013-2014 Luis Cordova <cordoval@gmail.com>\n\nThis source file is subject to the MIT license that is bundled\nwith this source code in the file LICENSE.";

    public function testCommand()
    {
        if (!$homeDir = getenv('GUSH_HOME')) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' in your \'phpunit.xml\'.');
        }

        $localDir = $homeDir.'/local_test';
        $gushLocalFilename = $localDir.'/.gush.yml';

        @mkdir($localDir, 0777, true);

        if (file_exists($gushLocalFilename)) {
            unlink($gushLocalFilename);
        }

        $this->config
            ->expects($this->at(0))
            ->method('get')
            ->with('local_config')
            ->will($this->returnValue($gushLocalFilename))
        ;

        $this->config
            ->expects($this->at(2))
            ->method('has')
            ->with('[adapters][github_enterprise]')
            ->will($this->returnValue(true))
        ;

        $this->config
            ->expects($this->at(2))
            ->method('has')
            ->with('[adapters][github_enterprise]')
            ->will($this->returnValue(true))
        ;

        $this->config
            ->expects($this->at(4))
            ->method('has')
            ->with('[issue_trackers][jira]')
            ->will($this->returnValue(true))
        ;

        $expected = [
            'adapter' => 'github_enterprise',
            'issue_tracker' => 'jira',
        ];

        $dialog = $this->expectDialogParameters();
        $tester = $this->getCommandTester($command = new InitCommand());
        $command->getHelperSet()->set($dialog, 'dialog');
        $tester->execute(
            [
                'command' => 'init',
            ],
            [
                'interactive' => true,
            ]
        );

        $this->assertFileExists($gushLocalFilename);

        $this->assertEquals($expected, Yaml::parse($gushLocalFilename));
    }

    public function testCommandWithMeta()
    {
        if (!$homeDir = getenv('GUSH_HOME')) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' in your \'phpunit.xml\'.');
        }

        $localDir = $homeDir.'/local_test';
        $gushLocalFilename = $localDir.'/.gush.yml';

        @mkdir($localDir, 0777, true);

        if (file_exists($gushLocalFilename)) {
            unlink($gushLocalFilename);
        }

        $this->config
            ->expects($this->at(0))
            ->method('get')
            ->with('local_config')
            ->will($this->returnValue($gushLocalFilename))
        ;

        $this->config
            ->expects($this->at(2))
            ->method('has')
            ->with('[adapters][github_enterprise]')
            ->will($this->returnValue(true))
        ;

        $this->config
            ->expects($this->at(2))
            ->method('has')
            ->with('[adapters][github_enterprise]')
            ->will($this->returnValue(true))
        ;

        $this->config
            ->expects($this->at(4))
            ->method('has')
            ->with('[issue_trackers][jira]')
            ->will($this->returnValue(true))
        ;

        $expected = [
            'adapter' => 'github_enterprise',
            'issue_tracker' => 'jira',
            'meta-header' => self::META_HEADER,
        ];

        $dialog = $this->expectDialogParameters(true);
        $template = $this->expectTemplate();

        $tester = $this->getCommandTester($command = new InitCommand());
        $command->getHelperSet()->set($dialog, 'dialog');
        $command->getHelperSet()->set($template, 'template');
        $tester->execute(
            [
                'command' => 'init',
                '--meta' => true,
            ],
            [
                'interactive' => true,
            ]
        );

        $this->assertFileExists($gushLocalFilename);

        $this->assertEquals($expected, Yaml::parse($gushLocalFilename));
    }

    private function expectDialogParameters($withMeta = false)
    {
        $dialog = $this->prophet->prophesize('Symfony\Component\Console\Helper\DialogHelper');

        $dialog->getName()->willReturn('dialog');
        $dialog->setHelperSet(Argument::any())->shouldBeCalled();
        $dialog->setInput(Argument::any())->shouldBeCalled();

        $dialog->select(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Choose adapter:'),
            ['github', 'github_enterprise'],
            0
        )->willReturn(1);

        $dialog->select(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Choose issue tracker:'),
            ['github', 'jira'],
            0
        )->willReturn(1);

        if ($withMeta) {
            $dialog->select(
                Argument::type('Symfony\Component\Console\Output\OutputInterface'),
                Argument::containingString('Choose License: '),
                ['mit', 'gpl3', 'no-license'],
                null
            )->willReturn(0);
        }

        return $dialog->reveal();
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
