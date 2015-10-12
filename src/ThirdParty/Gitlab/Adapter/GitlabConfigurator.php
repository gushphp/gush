<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Gitlab\Adapter;

use Gush\Adapter\DefaultConfigurator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
class GitlabConfigurator extends DefaultConfigurator
{
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $config = parent::interact($input, $output);

        $config['base_url'] = rtrim($config['base_url'], '/');
        $config['repo_domain_url'] = rtrim($config['repo_domain_url'], '/');

        return $config;
    }
}
