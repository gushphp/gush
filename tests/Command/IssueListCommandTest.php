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

use Gush\Command\Issue\IssueListCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class IssueListCommandTest extends BaseTestCase
{
    public function provideCommand()
    {
        return [
            [['--org' => 'gushphp', '--repo' => 'gush']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--type' => 'issue']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--assignee' => 'cordoval']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--mentioned' => 'cordoval']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--creator' => 'cordoval']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--milestone' => 'some good st...']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--label' => ['critical']]],
            [['--org' => 'gushphp', '--repo' => 'gush', '--state' => 'open']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--sort' => 'created']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--direction' => 'asc']],
            [['--org' => 'gushphp', '--repo' => 'gush', '--since' => '11 day ago']],
        ];
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommand($args)
    {
        $tester = $this->getCommandTester(new IssueListCommand());
        $tester->execute($args, ['interactive' => false]);

        $this->assertEquals(OutputFixtures::ISSUE_LIST, trim($tester->getDisplay(true)));
    }
}
