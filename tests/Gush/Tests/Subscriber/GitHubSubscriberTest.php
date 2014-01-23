<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Subscriber;

use Gush\Subscriber\GitHubSubscriber;
use Symfony\Component\Console\Command\Command;
use Gush\Feature\GitHubFeature;

class GitHubSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $subscriber;

    public function setUp()
    {
        $this->event = $this->getMockBuilder(
            'Gush\Event\CommandEvent'
        )->disableOriginalConstructor()->getMock();

        $this->command = $this->getMockBuilder(
            'Gush\Tests\Subscriber\TestGitHubCommand'
        )->disableOriginalConstructor()->getMock();

        $this->gitHelper = $this->getMock(
            'Gush\Helper\GitHelper'
        );

        $this->subscriber = new GitHubSubscriber($this->gitHelper);
    }

    public function testDecorateDefinition()
    {
        $this->event->expects($this->once())
            ->method('getCommand')
            ->will($this->returnValue($this->command))
        ;
        $this->gitHelper->expects($this->once())
            ->method('getVendorName')
            ->will($this->returnValue('foo'))
        ;
        $this->gitHelper->expects($this->once())
            ->method('getRepoName')
            ->will($this->returnValue('bar'))
        ;
        $this->command->expects($this->at(0))
            ->method('addOption')
            ->will($this->returnValue($this->command));
        ;
        $this->command->expects($this->at(1))
            ->method('addOption');
        ;

        $this->subscriber->decorateDefinition($this->event);
    }
}

class TestGitHubCommand extends Command implements GitHubFeature
{
}
