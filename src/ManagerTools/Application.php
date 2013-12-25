<?php

/*
 * This file is part of the Manager Tools.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ManagerTools;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Yaml\Yaml;

class Application extends BaseApplication
{
    protected $cwd;
    protected $parameters;

    public function setCwd($cwd)
    {
        $this->cwd = $cwd;
    }

    public function getCwd()
    {
        return $this->cwd;
    }

    public function readParameters()
    {
        $yaml = new Yaml();
        $parsed = $yaml->parse($this->getCwd().'/parameters.yml');
        $this->parameters = $parsed['parameters'];
    }

    public function getParameter($key)
    {
        return $this->parameters[$key];
    }
}