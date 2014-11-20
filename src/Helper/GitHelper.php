<?php

/*
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
     *
     * @deprecated Git commands should not be spread
     */
    public function runGitCommand($command)
    {
        return $this->processHelper->runCommand($command);
    }

    /**
     * @param string|null $defaultBranch
     *
     * @return string The branch name
     */
    public function getActiveBranchName($defaultBranch = null)
    {
        $activeBranch = $this->processHelper->runCommand('git rev-parse --abbrev-ref HEAD');

        // Detached head, use default branch
        if ('HEAD' === $activeBranch) {
            $activeBranch = $defaultBranch;
        }

        if (null === $activeBranch) {
            throw new \RuntimeException(
                'You are currently in a detached HEAD state, unable to get active branch-name.'.
                'Please run `git checkout` first.'
            );
        }

        return $activeBranch;
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

    public function getLastTag()
    {
        return $this->processHelper->runCommand('git describe --abbrev=0 --tags');
    }

    public function getLogBetweenCommits($start, $end)
    {
        return $this->processHelper->runCommand(sprintf('git log %s...%s --oneline --no-color', $start, $end));
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
            $segments = explode('-', $this->getActiveBranchName());
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

        $this->guardWorkingTreeReady();

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
                ],
            ]
        );

        $hash = trim($this->processHelper->runCommand('git rev-parse HEAD'));

        $this->processHelper->runCommand(['git', 'push', $baseRemote]);

        return $hash;
    }

    public function addNotes($notes, $commitHash, $ref)
    {
        $tmpName = $this->filesystemHelper->newTempFilename();
        file_put_contents($tmpName, $notes);

        $commands = [
            'git',
            'notes',
            '--ref='.$ref,
            'add',
            '-F',
            $tmpName,
            $commitHash,
        ];

        $this->processHelper->runCommand($commands, true);
    }

    public function pushRemote($remote, $ref, $setUpstream = false, $force = false)
    {
        $command = ['git', 'push', $remote];

        if ($setUpstream) {
            $command[] = '-u';
        }

        if ($force) {
            $command[] = '-f';
        }

        $command[] = $ref;

        $this->processHelper->runCommand($command);
    }

    public function pullRemote($remote, $ref = null, $setUpstream = false)
    {
        $command = ['git', 'pull'];

        if ($setUpstream) {
            $command[] = '-u';
        }

        $command[] = $remote;

        if ($ref) {
            $command[] = $ref;
        }

        $this->processHelper->runCommand($command);

    }

    public function remoteUpdate($remote = null)
    {
        $command = ['git', 'remote', 'update'];

        if ($remote) {
            $command[] = $remote;
        }

        $this->processHelper->runCommand($command);
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

    public function addRemote($name, $url)
    {
        $this->processHelper->runCommand(['git', 'remote', 'add', $name, $url]);
    }

    public function checkout($branchName, $createBranch = false)
    {
        $command = ['git', 'checkout'];

        if ($createBranch) {
            $command[] = '-b';
        }

        $command[] = $branchName;

        $this->processHelper->runCommand($command);
    }

    public function reset($commit, $type = 'soft')
    {
        $this->processHelper->runCommand(['git', 'reset', '--'.$type, $commit]);
    }

    public function add($path)
    {
        $this->processHelper->runCommand(['git', 'add', $path]);
    }

    public function switchBranchBase($branchName, $currentBase, $newBase, $newBranchName = null)
    {
        $this->guardWorkingTreeReady();

        $activeBranch = $this->getActiveBranchName($branchName);
        $this->checkout($branchName);

        if ($newBranchName) {
            // Switch to new branch so we can apply the rebase on the new branch
            $this->checkout($newBranchName, true);
        } else {
            $newBranchName = $branchName;
        }

        try {
            $this->processHelper->runCommand(['git', 'rebase', '--onto', $newBase, $currentBase, $newBranchName]);
        } catch (\Exception $e) {
            // Error, abort the rebase process
            $this->processHelper->runCommand(['git', 'rebase', '--abort'], true);
            $this->checkout($activeBranch);

            throw $e;
        }

        $this->checkout($activeBranch);
    }

    public function squashBranch($base, $branchName)
    {
        $this->guardWorkingTreeReady();

        $activeBranch = $this->getActiveBranchName($branchName);

        $this->checkout($branchName);

        $forkPoint = $this->processHelper->runCommand(
            sprintf(
                'git merge-base --fork-point %s %s',
                $base,
                $branchName
            )
        );

        $message = $this->splitLines(
            $this->processHelper->runCommand(
                sprintf(
                    'git rev-list %s..%s --reverse --format=%%s --max-count=1',
                    $forkPoint,
                    $branchName
                )
            )
        )[1];

        $this->reset($base);
        $this->commit($message, ['a']);
        $this->checkout($activeBranch);
    }

    public function syncWithRemote($remote, $branchName = null)
    {
        $this->guardWorkingTreeReady();

        $activeBranchName = $this->getActiveBranchName($branchName);

        if (null === $branchName) {
            $branchName = $activeBranchName;
        }

        $this->remoteUpdate($remote);

        if ($activeBranchName !== $branchName) {
            $this->checkout($branchName);
        }

        $this->reset('HEAD~1', 'hard');
        $this->pullRemote($remote, $branchName, true);

        if ($activeBranchName !== $branchName) {
            $this->checkout($activeBranchName);
        }
    }

    public function commit($message, array $options = [])
    {
        $params = '';

        foreach ($options as $option => $value) {
            if (is_int($option)) {
                $params[] = '-'.$value;
            } else {
                $params[] = '-'.$option;
                $params[] = $value;
            }
        }

        $tmpName = $this->filesystemHelper->newTempFilename();
        file_put_contents($tmpName, $message);

        $this->processHelper->runCommand(array_merge(['git', 'commit', '-F', $tmpName], $params));
    }

    private function guardWorkingTreeReady()
    {
        if (!$this->isWorkingTreeReady()) {
            throw new WorkingTreeIsNotReady();
        }
    }

    private function splitLines($output)
    {
        $output = trim($output);

        return ((string) $output === '') ? [] : preg_split('{\r?\n}', $output);
    }
}
