<?php

namespace Gush;

use Aura\Cli\Context\OptionFactory;

class Kernel
{
    public function run()
    {
        $gush = new GushPullRequestCommand(new OptionFactory());

        return GushPullRequestCommand::$name;
    }
}