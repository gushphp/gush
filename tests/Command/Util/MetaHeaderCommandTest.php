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

use Gush\Command\Util\MetaHeaderCommand;
use Gush\Helper\MetaHelper;
use Gush\Meta as Meta;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Filesystem\Filesystem;

class MetaHeaderCommandTest extends CommandTestCase
{
    const META_HEADER = <<<OET
This file is part of Your Package package.

(c) 2009-2016 You <you@yourdomain.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
OET;

    /**
     * @var string
     */
    private $srcDir;

    public function setUp()
    {
        parent::setUp();

        $this->srcDir = $this->getNewTmpFolder('src');

        // set-up the working dir
        (new Filesystem())->mirror(realpath(__DIR__.'/../../Fixtures/meta'), $this->srcDir);
    }

    public function testUpdateAllFileHeaders()
    {
        $command = new MetaHeaderCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(CommandTestCase::$localConfig, ['meta-header' => self::META_HEADER]),
            function (HelperSet $helperSet) {
                $helperSet->set($this->getMetaHelper());
            }
        );

        $this->setExpectedCommandInput(
            $command,
            [
                'yes', // php
                'yes', // text
                'yes', // css
                'yes', // twig
            ]
        );

        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Update css file(s)',
                'The following header will be set on 1 files:',
                '[UPDATED]: '.$this->srcDir.'/metatest.twig',
                '[UPDATED]: '.$this->srcDir.'/metatest.php',
                '[UPDATED]: '.$this->srcDir.'/metatest.js',
                '[UPDATED]: '.$this->srcDir.'/metatest.css',
                'The following header will be set on 1 files:',
                'css files were updated.',
                'twig files were updated.',
            ],
            $display
        );

        $this->assertEquals(OutputFixtures::HEADER_LICENSE_TWIG, file_get_contents($this->srcDir.'/metatest.twig'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_PHP, file_get_contents($this->srcDir.'/metatest.php'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_JS, file_get_contents($this->srcDir.'/metatest.js'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_CSS, file_get_contents($this->srcDir.'/metatest.css'));
    }

    public function testUpdateAllFileHeadersWhenNonInteractive()
    {
        $command = new MetaHeaderCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(CommandTestCase::$localConfig, ['meta-header' => self::META_HEADER]),
            function (HelperSet $helperSet) {
                $helperSet->set($this->getMetaHelper());
            }
        );

        $tester->execute([], ['interactive' => false]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Update css file(s)',
                'The following header will be set on 1 files:',
                '[UPDATED]: '.$this->srcDir.'/metatest.twig',
                '[UPDATED]: '.$this->srcDir.'/metatest.php',
                '[UPDATED]: '.$this->srcDir.'/metatest.js',
                '[UPDATED]: '.$this->srcDir.'/metatest.css',
                'The following header will be set on 1 files:',
                'css files were updated.',
                'twig files were updated.',
            ],
            $display
        );

        $this->assertEquals(OutputFixtures::HEADER_LICENSE_TWIG, file_get_contents($this->srcDir.'/metatest.twig'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_PHP, file_get_contents($this->srcDir.'/metatest.php'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_JS, file_get_contents($this->srcDir.'/metatest.js'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_CSS, file_get_contents($this->srcDir.'/metatest.css'));
    }

    public function testUpToDateFilesAreIgnored()
    {
        $command = new MetaHeaderCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(CommandTestCase::$localConfig, ['meta-header' => self::META_HEADER]),
            function (HelperSet $helperSet) {
                $helperSet->set($this->getMetaHelper());
            }
        );

        file_put_contents($this->srcDir.'/metatest.twig', OutputFixtures::HEADER_LICENSE_TWIG);

        $tester->execute([], ['interactive' => false]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Update css file(s)',
                'The following header will be set on 1 files:',
                '[IGNORED]: '.$this->srcDir.'/metatest.twig',
                '[UPDATED]: '.$this->srcDir.'/metatest.php',
                '[UPDATED]: '.$this->srcDir.'/metatest.js',
                '[UPDATED]: '.$this->srcDir.'/metatest.css',
                'The following header will be set on 1 files:',
                'css files were updated.',
                'twig files were updated.',
            ],
            $display
        );

        $this->assertEquals(OutputFixtures::HEADER_LICENSE_TWIG, file_get_contents($this->srcDir.'/metatest.twig'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_PHP, file_get_contents($this->srcDir.'/metatest.php'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_JS, file_get_contents($this->srcDir.'/metatest.js'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_CSS, file_get_contents($this->srcDir.'/metatest.css'));
    }

    public function testExcludedFilesAreNotUpdated()
    {
        $command = new MetaHeaderCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(CommandTestCase::$localConfig, ['meta-header' => self::META_HEADER, 'meta-exclude' => ['*.js']]),
            function (HelperSet $helperSet) {
                $helperSet->set($this->getMetaHelper());
            }
        );

        file_put_contents($this->srcDir.'/metatest.twig', OutputFixtures::HEADER_LICENSE_TWIG);

        $tester->execute([], ['interactive' => false]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Update css file(s)',
                'The following header will be set on 1 files:',
                '[IGNORED]: '.$this->srcDir.'/metatest.twig',
                '[UPDATED]: '.$this->srcDir.'/metatest.php',
                '[UPDATED]: '.$this->srcDir.'/metatest.css',
                'The following header will be set on 1 files:',
                'css files were updated.',
                'twig files were updated.',
            ],
            $display
        );

        $this->assertNotContains('[UPDATED]: '.$this->srcDir.'/metatest.js', $display);

        $this->assertEquals(OutputFixtures::HEADER_LICENSE_TWIG, file_get_contents($this->srcDir.'/metatest.twig'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_PHP, file_get_contents($this->srcDir.'/metatest.php'));
        $this->assertEquals(OutputFixtures::HEADER_LICENSE_CSS, file_get_contents($this->srcDir.'/metatest.css'));
        $this->assertEquals(
            file_get_contents(__DIR__.'/../../Fixtures/meta/metatest.js'),
            file_get_contents($this->srcDir.'/metatest.js')
        );
    }

    private function getMetaHelper()
    {
        $metasSupported = [
            'php' => new Meta\Base(),
            'js' => new Meta\Text(),
            'css' => new Meta\Text(),
            'twig' => new Meta\Twig(),
        ];

        return new MetaHelper($metasSupported);
    }

    protected function getGitHelper($isGitDir = true)
    {
        $files = [
            $this->srcDir.'/metatest.php',
            $this->srcDir.'/metatest.css',
            $this->srcDir.'/metatest.js',
            $this->srcDir.'/metatest.twig',
        ];

        $helper = parent::getGitHelper($isGitDir);
        $helper->listFiles()->willReturn($files);

        return $helper;
    }
}
