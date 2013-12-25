<?php

namespace ManagerTools;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    protected $cwd;

    public function setCwd($cwd)
    {
        $this->cwd = $cwd;
    }

    public function getCwd()
    {
        return $this->cwd;
    }
}