<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\Helper;

class GitHelper extends Helper
{
    /** @var \Gush\Helper\ProcessHelper */
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
     * @param string $command
     *
     * @return string
     */
    public function runGitCommand($command)
    {
        return $this->processHelper->runCommand($command);
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
        $output = $this->processHelper->runCommand('git remote show -n origin');

        $outputLines = $this->splitLines(trim($output));

        $foundRepoName = '';
        if (!in_array('Fetch', $outputLines)) {
            foreach ($outputLines as $line) {
                if ($line && preg_match('{^  Fetch URL: (.+@)*([\w\d\.]+):(.*)}', $line, $match)) {
                    preg_match('{(.+/)(.+)[.git]?}', $match[3], $secondMatch);
                    $foundRepoName = str_replace('.git', '', $secondMatch[2]);
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
        $output = $this->processHelper->runCommand('git remote show -n origin');

        $outputLines = $this->splitLines(trim($output));

        $foundVendorName = '';
        if (!in_array('Fetch', $outputLines)) {
            foreach ($outputLines as $line) {
                if ($line && preg_match('{^  Fetch URL: (.+@)*([\w\d\.]+):(.*)}', $line, $match)) {
                    preg_match('{(.+/)(.+)[.git]?}', $match[3], $secondMatch);
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
     *
     * @return string The tag name
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
     * @param array $options
     *
     * @return array Files in the git repository
     */
    public function listFiles($options = [])
    {
        $builder = $this->processHelper->getProcessBuilder(
            [
                'git',
                'ls-files',
            ]
        );

        foreach ($options as $name => $value) {
            $builder->setOption($name, $value);
        }

        $process = $builder->getProcess();
        $process->run();

        return explode(PHP_EOL, $process->getOutput());
    }

    public function getIssueNumber()
    {
        try {
            $branchName = $this->getBranchName();
            $segments = explode('-', $branchName);
            $issueNumber = $segments[0];
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid branch name, couldn\'t detect issue number.');
        }

        return $issueNumber;
    }
}
