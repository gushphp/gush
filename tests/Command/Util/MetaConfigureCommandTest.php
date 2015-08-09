<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Util;

use Gush\Command\Util\MetaConfigureCommand;
use Gush\Tests\Command\CommandTestCase;

class MetaConfigureCommandTest extends CommandTestCase
{
    const META_HEADER = <<<OET
This file is part of Gush package.

(c) 2013-%d Luis Cordova <cordoval@gmail.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
OET;

    public function testConfigureConfiguresMetaHeader()
    {
        $command = new MetaConfigureCommand();
        $tester = $this->getCommandTester(
            $command
        );

        $this->setExpectedCommandInput(
            $command,
            [
                '0', // mit
                'Gush', // Package Name
                'Luis Cordova <cordoval@gmail.com>', // Copyright Holder
                '2013', // Copyright Starts From
            ]
        );

        $tester->execute();

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches('Configuration file saved successfully.', $display);

        $this->assertEquals(sprintf(self::META_HEADER, date('Y')), $command->getConfig()->get('meta-header'));
    }

    protected function requiresRealConfigDir()
    {
        return true;
    }
}
