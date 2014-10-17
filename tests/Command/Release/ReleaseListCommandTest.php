<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Release;

use Gush\Command\Release\ReleaseListCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class ReleaseListCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function lists_releases()
    {
        $tester = $this->getCommandTester(new ReleaseListCommand());
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(trim(OutputFixtures::RELEASE_LIST), trim($tester->getDisplay(true)));
    }
}
