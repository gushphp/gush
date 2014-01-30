<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Process\Process;

class GitHelper extends Helper
{
    public function getName()
    {
        return 'git';
    }

    /**
     * @return string The branch name
     */
    public function getBranchName()
    {
        return $this->runGitCommand('git branch | grep "*" | cut -d " " -f 2');
    }

    /**
     * @return string The repository name
     */
    public function getRepoName()
    {
        $process = new Process(
            'git remote show -n origin | grep Fetch | cut -d "/" -f 2 | cut -d "." -f 1',
            getcwd()
        );
        $process->run();

        $output = trim($process->getOutput());
        if (empty($output)) {
            $process = new Process(
                'git remote show -n origin | grep Fetch | cut -d "/" -f 5 | cut -d "." -f 1',
                getcwd()
            );
            $process->run();
        }

        return trim($process->getOutput());
    }

    /**
     * @return string The vendor name
     */
    public function getVendorName()
    {
        $process = new Process('git remote show -n origin | grep Fetch | cut -d ":" -f 3 | cut -d "/" -f 1', getcwd());
        $process->run();

        $output = trim($process->getOutput());
        if (empty($output)) {
            $process = new Process(
                'git remote show -n origin | grep Fetch | cut -d ":" -f 3 | cut -d "/" -f 4',
                getcwd()
            );
            $process->run();
        }

        return trim($process->getOutput());
    }

    /**
     * @throws \RuntimeException
     * @return string            The tag name
     */
    public function getLastTagOnCurrentBranch()
    {
        return $this->runGitCommand('git describe --tags --abbrev=0 HEAD');
    }

    /**
     * @param string $gitCommandLine
     * @throws \RuntimeException
     *
     * @return string $output
     */
    public function runGitCommand($gitCommandLine)
    {
        $process = new Process($gitCommandLine, getcwd());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getOutput());
        }

        return trim($process->getOutput());
    }
}
