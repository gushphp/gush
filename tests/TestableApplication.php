<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Application;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Config;
use Symfony\Component\Console\Input\InputInterface;

class TestableApplication extends Application
{
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function buildAdapter()
    {
        return new TestAdapter($this->config);
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
