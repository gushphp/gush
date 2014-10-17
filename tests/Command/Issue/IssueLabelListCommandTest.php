<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Issue;

use Gush\Command\Issue\IssueLabelListCommand;
use Gush\Tests\Command\BaseTestCase;

class IssueLabelListCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function labels_an_issue_as_bug()
    {
        $tester = $this->getCommandTester(new IssueLabelListCommand());
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals('bug', trim($tester->getDisplay(true)));
    }
}
