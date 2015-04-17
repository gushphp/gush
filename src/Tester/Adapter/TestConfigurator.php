<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tester\Adapter;

use Gush\Adapter\Configurator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestConfigurator implements Configurator
{
    const USERNAME = 'test-user';
    const PASSWORD = 'secure-password';

    private $label;
    private $apiUrl;
    private $repoUrl;

    public function __construct($label, $apiUrl, $repoUrl)
    {
        $this->label = $label;
        $this->apiUrl = $apiUrl;
        $this->repoUrl = $repoUrl;
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $config = [];
        $config['base_url'] = $this->apiUrl;
        $config['repo_domain_url'] = $this->repoUrl;
        $config['authentication']['http-auth-type'] = self::AUTH_HTTP_PASSWORD;
        $config['authentication']['username'] = self::USERNAME;
        $config['authentication']['password-or-token'] = self::PASSWORD;

        return $config;
    }
}
