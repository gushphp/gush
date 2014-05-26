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

use Gush\Command\Release\ReleaseListCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Daniel T Leech <dantleech@gmail.com>
 */
class ReleaseListCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester(new ReleaseListCommand());
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(trim(OutputFixtures::RELEASE_LIST), trim($tester->getDisplay(true)));
    }
}
