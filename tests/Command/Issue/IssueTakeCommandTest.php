<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Issue;

use Gush\Command\Issue\IssueTakeCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;
use Prophecy\Argument;

class IssueTakeCommandTest extends BaseTestCase
{
    const SLUGIFIED_STRING = 'write-a-behat-test-to-launch-strategy';
    const TEST_TITLE = 'Write a behat test to launch strategy';

    /**
     * @test
     */
    public function takes_an_issue()
    {
        $this->expectsConfig();
        $this->config->get('base')->willReturn('master');

        $tester = $this->getCommandTester($command = new IssueTakeCommand());
        $command->getHelperSet()->set($this->expectTextHelper());
        $command->getHelperSet()->set($this->expectGitHelper('origin', 'origin/master'));

        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'issue_number' => TestAdapter::ISSUE_NUMBER],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf('[OK] Issue https://github.com/gushphp/gush/issues/%s taken!', TestAdapter::ISSUE_NUMBER),
            trim($tester->getDisplay(true))
        );
    }

    /**
     * @test
     */
    public function takes_an_issue_with_specific_base()
    {
        $this->expectsConfig();
        $this->config->get('base')->willReturn('master');

        $tester = $this->getCommandTester($command = new IssueTakeCommand());
        $command->getHelperSet()->set($this->expectTextHelper());
        $command->getHelperSet()->set($this->expectGitHelper('origin', 'origin/development'));

        $tester->execute(
            ['--org' => 'gushphp', 'issue_number' => TestAdapter::ISSUE_NUMBER, 'base_branch' => 'development'],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf('[OK] Issue https://github.com/gushphp/gush/issues/%s taken!', TestAdapter::ISSUE_NUMBER),
            trim($tester->getDisplay(true))
        );
    }

    /**
     * @test
     */
    public function takes_an_issue_with_specific_remote()
    {
        $this->expectsConfig();
        $this->config->get('base')->willReturn('master');

        $tester = $this->getCommandTester($command = new IssueTakeCommand());
        $command->getHelperSet()->set($this->expectTextHelper());
        $command->getHelperSet()->set($this->expectGitHelper('gushphp', 'gushphp/master'));

        $tester->execute(
            ['--org' => 'gushphp', 'issue_number' => TestAdapter::ISSUE_NUMBER, '--remote' => 'gushphp'],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf('[OK] Issue https://github.com/gushphp/gush/issues/%s taken!', TestAdapter::ISSUE_NUMBER),
            trim($tester->getDisplay(true))
        );
    }

    private function expectTextHelper()
    {
        $text = $this->prophet->prophesize('Gush\Helper\TextHelper');
        $text->setHelperSet(Argument::any())->shouldBeCalled();
        $text->getName()->willReturn('text');

        $text->slugify(
            sprintf('%d %s', TestAdapter::ISSUE_NUMBER, self::TEST_TITLE)
        )->willReturn(
            self::SLUGIFIED_STRING
        );

        return $text->reveal();
    }

    private function expectGitHelper($remote = 'origin', $baseBranch = 'origin/master')
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        $gitHelper->remoteUpdate($remote)->shouldBeCalled();
        $gitHelper->checkout($baseBranch)->shouldBeCalled();
        $gitHelper->checkout(self::SLUGIFIED_STRING, true)->shouldBeCalled();

        return $gitHelper->reveal();
    }
}
