<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\tests\Subscriber;

use Gush\Subscriber\GitRepoSubscriber;

class GitRepoSubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected $subscriber;
    protected $event;
    protected $command;
    protected $gitHelper;

    public function setUp()
    {
        $this->event = $this
            ->getMockBuilder('Gush\Event\CommandEvent')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->command = $this
            ->getMockBuilder('Gush\Tests\Subscriber\TestGitRepoCommand')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->gitHelper = $this
            ->getMockBuilder('Gush\Helper\GitHelper')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->subscriber = new GitRepoSubscriber($this->gitHelper);
    }

    /**
     * @test
     */
    public function decorates_a_definition()
    {
        $this->gitHelper->expects($this->once())
            ->method('isGitFolder')
            ->will($this->returnValue(true))
        ;
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
