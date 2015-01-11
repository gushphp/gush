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

use Gush\Command\Issue\IssueCopyCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class IssueCopyCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function copies_an_issue()
    {
        $tester = $this->getCommandTester(new IssueCopyCommand());
        $tester->execute([
            '--org' => 'gushphp',
            '--repo' => 'gush',
            'issue_number' => TestAdapter::ISSUE_NUMBER,
            'target_username' => 'dantleech',
            'target_repository' => 'gushphp',
            '--prefix' => '[SomePrefix] ',
            '--close' => true
        ], [
            'interactive' => false,
        ]);

        $this->assertEquals(OutputFixtures::ISSUE_COPY, trim($tester->getDisplay(true)));
    }
}
