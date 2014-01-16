<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace Gush\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Console\Command\Command;

class CommandEvent extends Event
{
    protected $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function getCommand()
    {
        return $this->command;
    }
}
