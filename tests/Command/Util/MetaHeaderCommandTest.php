<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Util;

use Gush\Command\Util\MetaHeaderCommand;
use Gush\Helper\MetaHelper;
use Gush\Meta as Meta;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class MetaHeaderCommandTest extends BaseTestCase
{
    private $command;
    private static $gitWorking;

    public function setUp()
    {
        parent::setUp();

        if (null === self::$gitWorking) {
            system('git version', $retVal);

            self::$gitWorking = $retVal === 0;
        }

        if (!self::$gitWorking) {
            $this->markTestSkipped('Git needs to be installed for this test.');
        }

        $this->command = $this->getTestCommand();
        $this->command->execute([], ['interactive' => false]);
    }

    /**
     * @test
     * @dataProvider metaFileProvider
     */
    public function runs_meta_header_command($file, $content)
    {
        $this->assertEquals($content, file_get_contents(__DIR__.'/../../../'.$file));
    }

    public function tearDown()
    {
        // Revert meta-files back to their original state, so the changed files don't get committed
        exec('git checkout tests/Fixtures/meta');
    }

    private function getTestCommand()
    {
        $metasSupported = [
            'php'  => new Meta\Base(),
            'js'   => new Meta\Text(),
            'css'  => new Meta\Text(),
            'twig' => new Meta\Twig(),
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
            __DIR__.'/../../Fixtures/meta/metatest.php',
            __DIR__.'/../../Fixtures/meta/metatest.css',
            __DIR__.'/../../Fixtures/meta/metatest.js',
            __DIR__.'/../../Fixtures/meta/metatest.twig',
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
