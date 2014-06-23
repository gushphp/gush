<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Configurator is the interface implemented by all Gush Adapter Configurator classes.
 */
interface Configurator
{
    const AUTH_HTTP_PASSWORD = 'http_password';

    const AUTH_HTTP_TOKEN = 'http_token';

    /**
     * Configures the adapter for usage.
     *
     * This methods is called for building the adapter configuration
     * which will be used every time a command is executed with the adapter.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array Validated and normalized configuration as associative array
     *
     * @throws \Exception When any of the validators returns an error
     */
    public function interact(InputInterface $input, OutputInterface $output);
}
