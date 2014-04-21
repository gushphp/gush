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

use Gush\Command\IssueListCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueListCommandTest extends BaseTestCase
{
    public function provideCommand()
    {
        return [
            [['--org' => 'gushphp', '--repo' => 'gush']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--type' => 'issue']],
        ];
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommand($args)
    {
        $tester = $this->getCommandTester(new IssueListCommand());
        $tester->execute($args, ['interactive' => false]);

        $this->assertEquals(OutputFixtures::ISSUE_LIST, trim($tester->getDisplay()));
    }
}
