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