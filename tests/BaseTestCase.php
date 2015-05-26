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
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Gush\Helper\OutputAwareInterface;
use Gush\Helper\ProcessHelper;
use Gush\Tests\Fixtures\Adapter\TestAdapterFactory;
use Gush\Tests\Fixtures\Adapter\TestIssueTrackerFactory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputAwareInterface;
use org\bovigo\vfs\vfsStream;

class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory|string
     */
    private $tmpFs;

    /**
     * Process counter for extra uniqueness.
     *
     * microtime() has proven to be problematic on fast CPU's.
     * But a counter alone is not enough as directories are not removed.
     *
     * @var int
     */
    private static $dirCounter = 0;

    protected function setUp()
    {
        if ('true' === getenv('GUSH_USE_FS')) {
            $this->tmpFs = sys_get_temp_dir();

            if (!$this->tmpFs) {
                $this->markTestSkipped('No system temp folder configured.');
            }
        } else {
            $this->tmpFs = vfsStream::setup();
        }
    }

    protected function getNewTmpFolder($name)
    {
        if ('true' === getenv('GUSH_USE_FS')) {
            $path = $this->tmpFs.'/'.$name.(++self::$dirCounter).microtime(true);
        } else {
            $path = $this->tmpFs->url().'/'.$name;
        }

        $this->assertFileNotExists($path);
        mkdir($path);

        return $path;
    }

    /**
     * @param Config        $config
     * @param \Closure|null $helperSetManipulator
     *
     * @return TestableApplication
     */
    protected function getApplication(Config $config = null, $helperSetManipulator = null)
    {
        if (null === $config) {
            $config = new Config('/home/user', '/temp/gush');
        }

        $adapterFactory = new AdapterFactory();
        $adapterFactory->register('github', 'GitHub', new TestAdapterFactory('github'));
        $adapterFactory->register('github_enterprise', 'GitHub Enterprise', new TestAdapterFactory('github_enterprise'));
        $adapterFactory->register('jira', 'Jira', new TestIssueTrackerFactory());

        $helperSetClosure = function (HelperSet $helperSet) use ($helperSetManipulator) {
            // Fake all system helpers to prevent actual execution
            $helperSet->set(new FilesystemHelper($this->getNewTmpFolder('tmp')));

            // Use a temp HelperSet to prevent double registering the prophecies (with other parameters)
            // causing failed expectations.
            $tmpHelperSet = new HelperSet();

            if (null !== $helperSetManipulator) {
                $helperSetManipulator($tmpHelperSet);
            }

            if (!$tmpHelperSet->has('process')) {
                $helperSet->set($this->getProcessHelper()->reveal());
            }

            if (!$tmpHelperSet->has('git_config')) {
                $helperSet->set($this->getGitConfigHelper()->reveal());
            }

            if (!$tmpHelperSet->has('git')) {
                $helperSet->set($this->getGitHelper()->reveal());
            }

            foreach ($tmpHelperSet->getIterator() as $helper) {
                $helperSet->set($helper);
            }
        };

        $application = new TestableApplication($adapterFactory, $config, $helperSetClosure);
        $application->setAutoExit(false);

        // Set the IO for Helpers, this should be run before any other listeners!
        $application->getDispatcher()->addListener(
            GushEvents::DECORATE_DEFINITION,
            function (ConsoleEvent $event) {
                $command = $event->getCommand();
                $input = $event->getInput();
                $output = $event->getOutput();

                foreach ($command->getHelperSet() as $helper) {
                    if ($helper instanceof InputAwareInterface) {
                        $helper->setInput($input);
                    }

                    if ($helper instanceof OutputAwareInterface) {
                        $helper->setOutput($output);
                    }
                }
            },
            255
        );

        return $application;
    }

    /**
     * @return ObjectProphecy
     */
    protected function getGitHelper($isGitFolder = true)
    {
        $gitHelper = $this->prophesize(GitHelper::class);
        $gitHelper->setHelperSet(Argument::any())->willReturn();
        $gitHelper->clearTempBranches()->willReturn(null);
        $gitHelper->getName()->willReturn('git');
        $gitHelper->isGitFolder()->willReturn($isGitFolder);

        return $gitHelper;
    }

    /**
     * @return ObjectProphecy
     */
    protected function getGitConfigHelper()
    {
        $helper = $this->prophesize(GitConfigHelper::class);
        $helper->setHelperSet(Argument::any())->willReturn();
        $helper->getName()->willReturn('git_config');

        return $helper;
    }

    /**
     * @return ObjectProphecy
     */
    protected function getProcessHelper()
    {
        $helper = $this->prophesize(ProcessHelper::class);
        $helper->setHelperSet(Argument::any())->willReturn();
        $helper->setOutput(Argument::any())->willReturn();
        $helper->getName()->willReturn('process');

        return $helper;
    }
}
