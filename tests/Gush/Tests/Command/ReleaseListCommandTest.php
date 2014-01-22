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

use Gush\Command\ReleaseListCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Daniel T Leech <dantleech@gmail.com>
 */
class ReleaseListCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $this->httpClient->whenGet('repos/cordoval/gush/releases')->thenReturn(array(
            [
                'id' => '123',
                'name' => 'This is a Release',
                'tag_name' => 'Tag name',
                'target_commitish' => '123123',
                'draft' => true,
                'prerelease' => 'yes',
                'created_at' => '2014-01-05',
                'published_at' => '2014-01-05',
            ],
        ));

        $tester = $this->getCommandTester(new ReleaseListCommand());
        $tester->execute(array('--org' => 'cordoval', '--repo' => 'gush'));

        $this->assertEquals(trim(OutputFixtures::RELEASE_LIST), trim($tester->getDisplay()));
    }
}
