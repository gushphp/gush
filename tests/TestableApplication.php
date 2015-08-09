<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Application;
use Gush\Config;
use Gush\Factory\AdapterFactory;

class TestableApplication extends Application
{
    /**
     * @var \closure
     */
    private $helperSetManipulator;

    /**
     * @param AdapterFactory $adapterFactory
     * @param Config         $config
     * @param \Closure       $helperSetManipulator
     */
    public function __construct(AdapterFactory $adapterFactory, Config $config, $helperSetManipulator)
    {
        $this->helperSetManipulator = $helperSetManipulator;

        parent::__construct($adapterFactory, $config, '@package_version@');
    }

    /**
     * {@inheritdoc}
     *
     * Overwritten so the helpers can be mocked.
     * This method is called within the constructor so setting it later
     * will not give the expected result.
     *
     * @return \Symfony\Component\Console\Helper\HelperSet
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        $callback = $this->helperSetManipulator;
        $callback($helperSet);

        return $helperSet;
    }
}
