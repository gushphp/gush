<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\PullRequestCreateCommand;
use Gush\Helper\GitHelper;
use Gush\Tester\Adapter\TestAdapter;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class PullRequestCreateCommandTest extends BaseTestCase
{
    public function provideCommand()
    {
        return [
            [[
                '--org'           => 'gushphp',
                '--repo'          => 'gush',
                '--source-branch' => 'issue-145',
                '--template'      => 'default',
                '--title'         => 'Test'
            ]],
        ];
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommand($args)
    {

        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay());
        $this->assertEquals('http://github.com/gushphp/gush/pull/' . TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommandWithIssue($args)
    {
        $args['--issue'] = '145';

        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay());
        $this->assertEquals('http://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @dataProvider provideCommand
     */
    public function testSourceOrgAutodetect($args)
    {
        $args['--verbose'] = true;

        $gitHelper = new GitHelper();
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay());
        $this->assertContains('Making PR from ' . $gitHelper->getVendorName() . ':issue-145 to gushphp:master', $res);
    }

    /**
     * @dataProvider provideCommand
     */
    public function testSourceOrgOption($args)
    {
        $args['--verbose']    = true;
        $args['--source-org'] = 'gushphp';

        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay());
        $this->assertContains('Making PR from ' . $args['--source-org'] . ':issue-145 to gushphp:master', $res);
    }
}
