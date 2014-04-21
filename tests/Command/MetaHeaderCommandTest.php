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

use Gush\Command\MetaHeaderCommand;
use Gush\Helper\MetaHelper;
use Gush\Meta as Meta;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class MetaHeaderCommandTest extends BaseTestCase
{
    private $command;

    public function setUp()
    {
        parent::setUp();
        $this->command = $this->getTestCommand();
        $this->command->execute([], ['interactive' => false]);
    }

    /**
     * @dataProvider metaFileProvider
     */
    public function testCommand($file, $content)
    {
        $this->assertEquals($content, file_get_contents($file));
    }

    public function tearDown()
    {
        // Revert meta-files back to their original state, so the changed files don't get committed
        exec('git checkout tests/Fixtures/meta');
    }

    private function getTestCommand()
    {
        $metasSupported = [
            'php'  => new Meta\Base,
            'js'   => new Meta\Base,
            'css'  => new Meta\Base,
            'twig' => new Meta\Twig,
        ];
        $gitHelper = $this->expectGitHelper();
        $tester = $this->getCommandTester($command = new MetaHeaderCommand());
        $command->getHelperSet()->set($gitHelper, 'git');
        $command->getHelperSet()->set(new MetaHelper($metasSupported), 'meta');

        return $tester;
    }

    private function expectGitHelper()
    {
        $files = [
            'tests/Fixtures/meta/metatest.php',
            'tests/Fixtures/meta/metatest.css',
            'tests/Fixtures/meta/metatest.js',
            'tests/Fixtures/meta/metatest.twig',
        ];

        $gitHelper = $this
            ->getMockBuilder('Gush\Helper\GitHelper')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $gitHelper->expects($this->once())
            ->method('listFiles')
            ->will($this->returnValue($files))
        ;

        return $gitHelper;
    }

    public function metaFileProvider()
    {
        return [
            ['tests/Fixtures/meta/metatest.twig', OutputFixtures::HEADER_LICENSE_TWIG],
            ['tests/Fixtures/meta/metatest.php',  OutputFixtures::HEADER_LICENSE_PHP],
            ['tests/Fixtures/meta/metatest.js',   OutputFixtures::HEADER_LICENSE_JS],
            ['tests/Fixtures/meta/metatest.css',  OutputFixtures::HEADER_LICENSE_CSS],
        ];
    }
}
