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

use Github\Client;
use Gush\Command\Core\CoreConfigureCommand;
use Gush\Factory\AdapterFactory;
use Gush\Tester\Adapter\TestConfigurator;
use Gush\Tester\QuestionToken;
use Gush\Tests\Command\BaseTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class CoreConfigureCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function core_configure()
    {
        if (!$homeDir = getenv('GUSH_HOME')) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' in your \'phpunit.xml\'.');
        }

        $gushFilename = $homeDir.'/.gush.yml';

        $expected = [
            'parameters' => [
                'cache-dir' => $homeDir.'/cache',
                'adapters' => [
                    'github' => [
                        'authentication' => [
                            'http-auth-type' => Client::AUTH_HTTP_PASSWORD,
                            'username' => TestConfigurator::USERNAME,
                            'password-or-token' => TestConfigurator::PASSWORD,
                        ],
                        'base_url' => 'https://api.github.com/',
                        'repo_domain_url' => 'https://github.com',
                    ],
                ],
                'issue_trackers' => [
                    'github' => [
                        'authentication' => [
                            'http-auth-type' => Client::AUTH_HTTP_PASSWORD,
                            'username' => TestConfigurator::USERNAME,
                            'password-or-token' => TestConfigurator::PASSWORD,
                        ],
                        'base_url' => 'https://api.github.com/',
                        'repo_domain_url' => 'https://github.com',
                    ],
                ],
                'home' => $homeDir,
                'home_config' => $homeDir.'/.gush.yml',
                'adapter' => 'github',
                'issue_tracker' => 'github',
            ]
        ];

        @mkdir($homeDir, 0777, true);

        if (file_exists($gushFilename)) {
            unlink($gushFilename);
        }

        $tester = $this->getCommandTester($command = new CoreConfigureCommand());
        $this->expectDialogParameters($command->getHelperSet(), $command);

        $tester->execute(
            [
                'command' => 'core:configure',
            ],
            [
                'interactive' => true,
            ]
        );

        $this->assertFileExists($gushFilename);
        $this->assertEquals($expected, Yaml::parse($gushFilename));
    }

    private function expectDialogParameters(HelperSet $helperSet, Command $command)
    {
        $styleHelper = $this->prophet->prophesize('Gush\Helper\StyleHelper');
        $styleHelper->getName()->willReturn('gush_style');
        $styleHelper->setHelperSet(Argument::any())->shouldBeCalled();

        // Common styling, no need to test
        $styleHelper->title(Argument::any())->shouldBeCalled();
        $styleHelper->section(Argument::any())->shouldBeCalled();
        $styleHelper->text(Argument::any())->shouldBeCalled();
        $styleHelper->newLine(Argument::any())->shouldBeCalled();
        $styleHelper->success(Argument::any())->shouldBeCalled();

        $styleHelper->numberedChoice('Choose adapter', Argument::any())->willReturn('github');
        $styleHelper->confirm('Do you want to configure other adapters?', false)->willReturn(false);

        // Defaulting
        $styleHelper->confirm('Would you like to make "GitHub" the default repository manager?', true)->willReturn(true);
        $styleHelper->confirm('Would you like to make "GitHub" the default issue tracker?', true)->willReturn(true);

        $helperSet->set($styleHelper->reveal());
    }
}
