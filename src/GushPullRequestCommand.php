<?php

namespace Gush;

class GushPullRequestCommand extends BaseCommand
{
    public static $name = 'pr';

    protected function init()
    {
        $this->setSummary('A single-line summary.');
        $this->setUsage('<arg1> <arg2>');
        $this->setOptions(array(
            'f,foo' => "The -f/--foo option description",
            'bar::' => "The --bar option description",
        ));
        $this->setDescr("A multi-line description of the command.");
    }
}
