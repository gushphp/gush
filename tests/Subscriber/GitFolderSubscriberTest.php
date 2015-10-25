<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Subscriber;

use Gush\Subscriber\GitFolderSubscriber;
use Gush\Tests\Fixtures\Command\GitFolderCommand;
use Gush\Tests\Fixtures\Command\GitRepoCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class GitFolderSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function fire_no_error_when_in_git_folder()
    {
        $command = new GitFolderCommand();

        $commandEvent = new ConsoleCommandEvent(
            $command,
            $this->getMock('Symfony\Component\Console\Input\InputInterface'),
            $this->getMock('Symfony\Component\Console\Output\OutputInterface')
        );

        $helper = $this->getGitHelper();

        $subscriber = new GitFolderSubscriber($helper);
        $subscriber->initialize($commandEvent);

        $this->assertTrue($helper->isGitFolder());
    }

    /**
     * @test
     */
    public function fire_no_error_when_not_a_git_featured_command()
    {
        $command = new GitRepoCommand();

        $commandEvent = new ConsoleCommandEvent(
            $command,
            $this->getMock('Symfony\Component\Console\Input\InputInterface'),
            $this->getMock('Symfony\Component\Console\Output\OutputInterface')
        );

        $helper = $this->getGitHelper(false);

        $subscriber = new GitFolderSubscriber($helper);
        $subscriber->initialize($commandEvent);

        $this->assertFalse($helper->isGitFolder());
    }

    /**
     * @test
     */
    public function throws_user_exception_when_not_in_git_folder()
    {
        $command = new GitFolderCommand();

        $commandEvent = new ConsoleCommandEvent(
            $command,
            $this->getMock('Symfony\Component\Console\Input\InputInterface'),
            $this->getMock('Symfony\Component\Console\Output\OutputInterface')
        );

        $helper = $this->getGitHelper(false);

        $subscriber = new GitFolderSubscriber($helper);

        $this->setExpectedException('Gush\Exception\UserException');

        $subscriber->initialize($commandEvent);
    }

    private function getGitHelper($isGitFolder = true)
    {
        $helper = $this->prophesize('Gush\Helper\GitHelper');
        $helper->isGitFolder()->willReturn($isGitFolder);

        return $helper->reveal();
    }
}
