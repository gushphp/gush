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

use Gush\Command\IssueCloseCommand;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueCloseCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $this->httpClient->whenPatch(
            '/repos/cordoval/gush/issues/12',
            json_encode(['state' => 'closed'])
        )->thenReturn(
            [
                'number' => 12
            ]
        );

        $tester = $this->getCommandTester(new IssueCloseCommand());
        $tester->execute(array('org' => 'cordoval'));

        $this->assertEquals('x', trim($tester->getDisplay()));
    }
}
