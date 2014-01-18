<?php

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
