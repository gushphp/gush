<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Application;
use Gush\Config;
use Gush\Tester\Adapter\TestAdapter;

class TestableApplication extends Application
{
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function buildAdapter($adapter, array $config = null)
    {
        return new TestAdapter();
    }

    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
