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

use Gush\Exception\UnknownRemoteException;
use Gush\Exception\WorkingTreeIsNotReady;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Filesystem\Filesystem;

class GitHelper extends Helper
{
    const UNDEFINED_ORG = 1;
    const UNDEFINED_REPO = 2;

    /** @var \Gush\Helper\ProcessHelper */
    protected $processHelper;

    /**
     * @var FilesystemHelper
     */
    protected $filesystemHelper;

    public function __construct(ProcessHelper $processHelper, FilesystemHelper $filesystemHelper)
    {
        $this->processHelper = $processHelper;
        $this->filesystemHelper = $filesystemHelper;
    }

    public function getName()
    {
        return 'git';
    }

    /**
     * @param string $command
     *
     * @return string Result of running the git command
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
     * @return bool Whether we are inside a git folder or not
     */
    public function isGitFolder()
    {
        try {
            $this->processHelper->runCommand('git rev-parse', false, null, true);
        } catch (\RuntimeException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return string The repository name
     */
    public function getRepoName()
    {
        $output = $this->processHelper->runCommand('git remote show -n origin', false, null, true);

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
        $output = $this->processHelper->runCommand('git remote show -n origin', false, null, true);

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

        return $this->splitLines($process->getOutput());
    }

    public function getIssueNumber()
    {
        try {
            $segments = explode('-', $this->getBranchName());
            $issueNumber = $segments[0];
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid branch name, couldn\'t detect issue number.');
        }

        return $issueNumber;
    }

    /**
     * @param string $base         The base branch name
     * @param string $sourceBranch The source branch name
     *
     * @return string The title of the first commit on sourceBranch off of base
     */
    public function getFirstCommitTitle($base, $sourceBranch)
    {
        $forkPoint = $this->processHelper->runCommand(
            sprintf(
                'git merge-base --fork-point %s %s',
                $base,
                $sourceBranch
            )
        );

        $lines = $this->processHelper->runCommand(
            sprintf(
                'git rev-list %s..%s --reverse --oneline',
                $forkPoint,
                $sourceBranch
            )
        );

        return substr(strtok($lines, "\n"), 8);
    }

    /**
     * @param string $sourceRemote  Remote name for pulling as registered in the .git/config
     * @param string $baseRemote    Remote name for pushing as registered in the .git/config
     * @param string $base          The base branch name
     * @param string $sourceBranch  The source branch name
     * @param string $commitMessage Commit message to use for the merge-commit
     * @param int    $options       Options (reserved for feature usage)
     *
     * @throws WorkingTreeIsNotReady
     *
     * @return string Thew merge-commit hash
     */
    public function mergeRemoteBranch($sourceRemote, $baseRemote, $base, $sourceBranch, $commitMessage, $options = null)
    {
        if (!$this->hasGitConfig(sprintf('remote.%s.url', $sourceRemote))) {
            if (!$this->hasGitConfig('remote.origin.url')) {
                throw new UnknownRemoteException($sourceRemote);
            }

            $sourceRemote = 'origin';
        }

        if (!$this->isWorkingTreeReady()) {
            throw new WorkingTreeIsNotReady();
        }

        $tmpName = $this->filesystemHelper->newTempFilename();
        file_put_contents($tmpName, $commitMessage);

        $this->processHelper->runCommands(
            [
                [
                    'line' => 'git remote update',
                    'allow_failures' => false,
                ],
                [
                    'line' => 'git checkout '.$base,
                    'allow_failures' => false,
                ],
                [
                    'line' => 'git pull --ff-only',
                    'allow_failures' => false,
                ],
                [
                    'line' => ['git', 'merge', '--no-ff', '--no-commit', $sourceRemote.'/'.$sourceBranch],
                    'allow_failures' => false,
                ],
                [
                    'line' => ['git', 'commit', '-F', $tmpName],
                    'allow_failures' => false,
                ]
            ]
        );

        $hash = trim($this->processHelper->runCommand('git rev-parse HEAD'));

        $this->processHelper->runCommand(['git', 'push', $baseRemote]);

        return $hash;
    }

    public function isWorkingTreeReady()
    {
        return '' === trim($this->processHelper->runCommand('git status --porcelain --untracked-files=no'));
    }

    public function hasGitConfig($config, $section = 'local', $expectedValue = null)
    {
        $value = trim(
                $this->processHelper->runCommand(
                sprintf(
                    'git config --%s --get %s',
                    $section,
                    $config
                ),
                true
            )
        );

        if ('' === $value || (null !== $expectedValue && $value !== $expectedValue)) {
            return false;
        }

        return true;
    }

    public function setGitConfig($config, $value, $overwrite = false, $section = 'local')
    {
        if ($this->hasGitConfig($config, $value) && $overwrite) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to set git config "%s" at %s, because the value is already set.',
                    $config,
                    $section
                )
            );
        }

        $this->processHelper->runCommand(
            sprintf(
                'git config "%s" "%s" --%s',
                $config,
                $value,
                $section
            )
        );
    }

    private function splitLines($output)
    {
        $output = trim($output);

        return ((string) $output === '') ? [] : preg_split('{\r?\n}', $output);
    }
}
