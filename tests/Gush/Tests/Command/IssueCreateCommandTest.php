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

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueCreateCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $this->httpClient->whenGet('repos/cordoval/gush/labels')->thenReturn(
            [

            ]
        );

        $tester = $this->getCommandTester(new IssueCreateCommand());
        $tester->execute(array('org' => 'cordoval'));

        $this->assertEquals('x', trim($tester->getDisplay()));
    }
}
