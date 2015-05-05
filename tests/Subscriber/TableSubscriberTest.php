<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Subscriber;

use Gush\Command\BaseCommand;
use Gush\Tests\BaseTestCase;
use Gush\Tests\Command\CommandTester;
use Gush\Tests\Fixtures\Command\TemplateTestCommand;

class TableSubscriberTest extends BaseTestCase
{
    public function testAddsOptionsForTemplateFeaturedCommand()
    {
        $command = new TemplateTestCommand();
        $commandDef = $command->getDefinition();

        $this->assertFalse($commandDef->hasOption('table-layout'));

        $this->runCommandTest($command);

        $this->assertTrue($commandDef->hasOption('table-layout'));
        $this->assertTrue($commandDef->hasOption('table-no-header'));
        $this->assertTrue($commandDef->hasOption('table-no-footer'));
    }

    public function provideTemplateTypes()
    {
        return [
            ['default', true],
            ['borderless', true],
            ['compact', true],
            ['foobar', false],
        ];
    }

    /**
     * @test
     * @dataProvider provideTemplateTypes
     */
    public function testThrowsExceptionOnUnsupportedTemplateType($layoutName, $valid)
    {
        $command = new TemplateTestCommand();

        if (false === $valid) {
            $this->setExpectedException('InvalidArgumentException', 'must be passed one of');
        }

        $this->runCommandTest($command, ['--table-layout' => $layoutName]);
    }

    /**
     * @param BaseCommand $command
     * @param array       $input
     *
     * @return CommandTester
     */
    private function runCommandTest(BaseCommand $command, array $input = [])
    {
        $application = $this->getApplication();
        $command->setApplication($application);

        $commandTest = new CommandTester($command);
        $commandTest->execute(array_merge($input, ['command' => $command->getName()]), ['decorated' => false]);

        return $commandTest;
    }
}
