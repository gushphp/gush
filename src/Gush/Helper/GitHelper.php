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
        return $this->runGitCommand('git rev-parse --abbrev-ref HEAD');
    }

    /**
     * @return string The repository name
     */
    public function getRepoName()
    {
        $process = new Process('git remote show -n origin', getcwd());
        $process->run();

        $outputLines = $this->splitLines(trim($process->getOutput()));

        $foundRepoName = '';
        if (!in_array('Fetch', $outputLines)) {
            foreach ($outputLines as $line) {
                if ($line && preg_match('{^  Fetch URL: (.+@)*([\w\d\.]+):(.*)}', $line, $match)) {
                    preg_match('{(.+/)(.+).git}', $match[3], $secondMatch);
                    $foundRepoName = $secondMatch[2];
                    break;
                }
            }
        }

        return $foundRepoName;
    }

    /**
     * @return string The vendor name
     */
    public function getVendorName()
    {
        $process = new Process('git remote show -n origin', getcwd());
        $process->run();

        $outputLines = $this->splitLines(trim($process->getOutput()));

        $foundVendorName = '';
        if (!in_array('Fetch', $outputLines)) {
            foreach ($outputLines as $line) {
                if ($line && preg_match('{^  Fetch URL: (.+@)*([\w\d\.]+):(.*)}', $line, $match)) {
                    preg_match('{(.+/)(.+).git}', $match[3], $secondMatch);
                    $exploded = explode('/', $secondMatch[1]);
                    $foundVendorName = $exploded[count($exploded) - 2];
                    break;
                }
            }
        }

        return $foundVendorName;
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
     * @param  string            $gitCommandLine
     * @throws \RuntimeException
     *
     * @return string $output
     */
    public function runGitCommand($gitCommandLine)
    {
        $process = new Process($gitCommandLine, getcwd());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }

    private function splitLines($output)
    {
        $output = trim($output);

        return ((string) $output === '') ? [] : preg_split('{\r?\n}', $output);
    }
}
