<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestCreateCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;
use Prophecy\Argument;

class PullRequestCreateCommandTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->config->get('base')->willReturn('master');
        $this->config->get('remove-promote')->willReturn(false);
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function opens_pull_request_with_arguments($args)
    {
        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper());

        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertEquals('https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function opens_pull_request_from_an_issue($args)
    {
        $args['--issue'] = '145';

        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());

        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertEquals('https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function opens_pull_request_autodetecting_current_branch_and_default_master($args)
    {
        $args['--verbose'] = true;

        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper());

        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertContains('Making PR from cordoval:issue-145 to gushphp:master', $res);
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function opens_pull_request_to_a_specific_organization_or_username($args)
    {
        $args['--verbose'] = true;
        $args['--source-org'] = 'gushphp';

        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper());
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertContains('Making PR from '.$args['--source-org'].':issue-145 to gushphp:master', $res);
    }

    private function expectGitHelper()
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        $gitHelper->getFirstCommitTitle('master', 'issue-145')->willReturn('some good title');
        $gitHelper->getActiveBranchName()->willReturn('issue-145');

        return $gitHelper->reveal();
    }

    public function provideCommand()
    {
        return [
            [
                [
                    '--org' => 'gushphp',
                    '--repo' => 'gush',
                    '--source-branch' => 'issue-145',
                    '--template' => 'default',
                    '--title' => 'Test',
                ]
            ],
        ];
    }
}
