<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Issue;

use Gush\Command\Issue\IssueListCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class IssueListCommandTest extends CommandTestCase
{
    /**
     * @dataProvider provideCommand
     */
    public function testListsIssuesWithArguments($args)
    {
        $tester = $this->getCommandTester(new IssueListCommand());
        $tester->execute($args);

        $display = $tester->getDisplay();

        if (isset($args['--type']) && 'issue' === $args['--type']) {
            $rows = [
                ['2', 'open', '', 'hard issue', 'weaverryan', 'cordoval', 'some_good_stuff', 'critic', '1969-12-31 10:00', 'https://github.com/gushphp/gush/issues/2'],
            ];
        } else {
            $rows = [
                ['1', 'open', 'PR', 'easy issue', 'cordoval', 'cordoval', 'good_release', 'critic,easy pick', '1969-12-31 10:00', 'https://github.com/gushphp/gush/issues/1'],
                ['2', 'open', '', 'hard issue', 'weaverryan', 'cordoval', 'some_good_stuff', 'critic', '1969-12-31 10:00', 'https://github.com/gushphp/gush/issues/2'],
            ];
        }

        $this->assertTableOutputMatches(
            ['#', 'State', 'PR?', 'Title', 'User', 'Assignee', 'Milestone', 'Labels', 'Created', 'Link'],
            $rows,
            $display
        );
    }

    public function provideCommand()
    {
        return [
            [[]],
            [['--type' => 'issue']],
            [['--assignee' => 'cordoval']],
            [['--mentioned' => 'cordoval']],
            [['--creator' => 'cordoval']],
            [['--milestone' => 'some good st...']],
            [['--label' => ['critical']]],
            [['--state' => 'open']],
            [['--sort' => 'created']],
            [['--direction' => 'asc']],
            [['--since' => '11 day ago']],
        ];
    }
}
