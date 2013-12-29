<?php

/*
 * This file is part of the Manager Tools.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ManagerTools\Command;

use Symfony\Component\Console\Command\Command;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class BaseCommand extends Command
{
    /**
     * Gets the Github's Client
     *
     * @return \Github\Client
     */
    protected function getGithubClient()
    {
        return $this->getApplication()->getGithubClient();
    }

    /**
     * Gets a specific parameter
     *
     * @param mixed $key
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->getApplication()->getParameter($key);
    }
} 
