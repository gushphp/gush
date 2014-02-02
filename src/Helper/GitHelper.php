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
    protected $processHelper;

    public function __construct(ProcessHelper $processHelper)
    {
        $this->processHelper = $processHelper;
    }

    public function getName()
    {
        return 'git';
    }

    /**
     * @return string The branch name
     */
    public function getBranchName()
    {
        return $this->processHelper->runCommand('git rev-parse --abbrev-ref HEAD');
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
                    preg_match('{(.+/)(.+)}', $match[3], $secondMatch);
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
                    preg_match('{(.+/)(.+)}', $match[3], $secondMatch);
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
        return $this->processHelper->runCommand('git describe --tags --abbrev=0 HEAD');
    }

    private function splitLines($output)
    {
        $output = trim($output);

        return ((string) $output === '') ? [] : preg_split('{\r?\n}', $output);
    }

    /**
     * @return array  Files in the git repository
     */
    public function lsFiles($options = array())
    {
        $builder = $this->processHelper->getProcessBuilder([
            'git',
            'ls-files'
        ]);

        foreach ($options as $name => $value) {
            $builder->setOption($name, $value);
        }

        $process = $builder->getProcess();
        $process->run();

        return explode("\n", $process->getOutput());
    }
}
