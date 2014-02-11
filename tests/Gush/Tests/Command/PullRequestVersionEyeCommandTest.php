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

use Gush\Command\PullRequestVersionEyeCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class VersionEyeCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $command = new PullRequestVersionEyeCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute(['--org' => 'cordoval', '--repo' => 'gush-sandbox'], ['interactive' => false]);

        $res = trim($tester->getDisplay());
        $this->assertEquals(OutputFixtures::PULL_REQUEST_VERSIONEYE, $res);
    }
}
