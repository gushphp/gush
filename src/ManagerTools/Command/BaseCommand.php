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
use Symfony\Component\Process\Process;

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
     * @param  mixed $key
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->getApplication()->getParameter($key);
    }

    /**
     * @return string The repository name
     */
    protected function getRepoName()
    {
        $process = new Process('git remote show -n origin | grep Fetch | cut -d "/" -f 2 | cut -d "." -f 1', getcwd());
        $process->run();

        return trim($process->getOutput());
    }

    /**
     * @return string The vendor name
     */
    protected function getVendorName()
    {
        $process = new Process('git remote show -n origin | grep Fetch | cut -d ":" -f 3 | cut -d "/" -f 1', getcwd());
        $process->run();

        return trim($process->getOutput());
    }

    /**
     * @return string The branch name
     */
    protected function getBranchName()
    {
        $process = new Process('git branch | grep "*" | cut -d " " -f 2', getcwd());
        $process->run();

        return trim($process->getOutput());
    }
}
