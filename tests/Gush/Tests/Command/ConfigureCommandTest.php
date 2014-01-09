<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\ConfigureCommand;
use Gush\Tests\TestableApplication;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class ConfigureCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $homeDir = getenv('GUSH_HOME');
        $gushFilename = $homeDir.'/.gush.yml';

        if (!$homeDir) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' in your \'phpunit.xml\'.');
        }

        $expected = ['parameters' => [
            'cache-dir' => $homeDir.'/cache',
            'home' => $homeDir,
            'github' => ['username' => 'foo', 'password' => 'bar']
        ]];

        @mkdir($homeDir, 0777, true);

        $this->httpClient->whenGet('authorizations')->thenReturn([]);

        // Mock the DialogHelper
        $dialog = $this->getMock(
            'Symfony\Component\Console\Helper\DialogHelper',
            ['askAndValidate', 'askHiddenResponseAndValidate']
        );

        // username
        $dialog->expects($this->at(0))
            ->method('askAndValidate')
            ->will($this->returnValue('foo'));
        // password
        $dialog->expects($this->at(1))
            ->method('askHiddenResponseAndValidate')
            ->will($this->returnValue('bar'));
        // cache-dir
        $dialog->expects($this->at(2))
            ->method('askAndValidate')
            ->will($this->returnValue($homeDir.'/cache'));

        $application = new TestableApplication();
        $application->add(new ConfigureCommand());

        $application->setGithubClient($this->buildGithubClient());

        $command = $application->find('configure');
        $command->getHelperSet()->set($dialog, 'dialog');

        $tester = new CommandTester($command);
        $tester->execute(['command' => 'configure']);

        $this->assertFileExists($gushFilename);

        $this->assertEquals($expected, Yaml::parse($gushFilename));
    }
}
