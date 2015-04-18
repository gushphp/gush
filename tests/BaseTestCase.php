<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Config;
use Gush\Event\GushEvents;
use Gush\Factory\AdapterFactory;
use Gush\Helper\FilesystemHelper;
use Gush\Helper\OutputAwareInterface;
use Gush\Tester\Adapter\TestAdapterFactory;
use Gush\Tester\Adapter\TestIssueTrackerFactory;
use Gush\Tests\TestableApplication;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Tester\CommandTester;

class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $rootFs;

    protected function setUp()
    {
        $this->rootFs = vfsStream::setup('tests');
    }

    /**
     * @return ObjectProphecy
     */
    protected function getGitHelper()
    {
        $gitHelper = $this->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        return $gitHelper;
    }

    /**
     * @return ObjectProphecy
     */
    protected function getGitConfigHelper()
    {
        $helper = $this->prophesize('Gush\Helper\GitConfigHelper');
        $helper->setHelperSet(Argument::any())->shouldBeCalled();
        $helper->getName()->willReturn('git_config');

        return $helper;
    }

    /**
     * @return ObjectProphecy
     */
    protected function getProcessHelper()
    {
        $helper = $this->prophesize('Gush\Helper\ProcessHelper');
        $helper->setHelperSet(Argument::any())->shouldBeCalled();
        $helper->setOutput(Argument::any())->shouldBeCalled();
        $helper->getName()->willReturn('process');

        return $helper;
    }

    /**
     * @return ObjectProphecy
     */
    protected function getEditorHelper()
    {
        // XXX Doesn't really have to be mocked but creates temp files. helper needs updating

        $helper = $this->prophesize('Gush\Helper\EditorHelper');
        $helper->setHelperSet(Argument::any())->shouldBeCalled();
        $helper->setOutput(Argument::any())->shouldBeCalled();
        $helper->getName()->willReturn('editor');

        return $helper;
    }
}
