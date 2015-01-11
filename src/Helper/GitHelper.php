<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Gush\Exception\CannotSquashMultipleAuthors;
use Gush\Exception\WorkingTreeIsNotReady;
use Gush\Operation\RemoteMergeOperation;
use Gush\Util\StringUtil;
use Symfony\Component\Console\Helper\Helper;

class GitHelper extends Helper
{
    const UNDEFINED_ORG = 1;
    const UNDEFINED_REPO = 2;

    /** @var ProcessHelper */
    private $processHelper;

    /**
     * @var FilesystemHelper
     */
    private $filesystemHelper;

    /**
     * @var GitConfigHelper
     */
    private $gitConfigHelper;

    /**
     * @var string
     */
    private $stashedBranch;

    /**
     * @var string[]
     */
    private $tempBranches = [];

    public function __construct(
        ProcessHelper $processHelper,
        GitConfigHelper $gitConfigHelper,
        FilesystemHelper $filesystemHelper
    ) {
        $this->processHelper = $processHelper;
        $this->filesystemHelper = $filesystemHelper;
        $this->gitConfigHelper = $gitConfigHelper;
    }

    public function getName()
    {
        return 'git';
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
     * Tries to restore back to the original branch the user was
     * in (before executing any command).
     */
    public function restoreStashedBranch()
    {
        if (null === $this->stashedBranch) {
            return;
        }

        if (!$this->isWorkingTreeReady()) {
            throw new \RuntimeException(
                sprintf(
                    'The Git working tree has uncommitted changes, unable to checkout your working branch "%s"'."\n".
                    'Please resolve this failure manually.',
                    $this->stashedBranch
                )
            );
        }

        $this->checkout($this->stashedBranch);
        $this->stashedBranch = null;
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
        $outputLines = StringUtil::splitLines($output);

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
     * Returns the log commits between two ranges (either commit or branch-name).
     *
     * Returned result is an array like:
     * [
     *     ['sha' => '...', 'author' => '...', 'subject' => '...', 'message' => '...'],
     * ]
     *
     * Note;
     * - Commits are by default returned in order of oldest to newest.
     * - sha is the full commit-hash
     * - author is the author name and e-mail address like "My Name <someone@example.com>"
     * - Message contains the subject followed by two new lines and the actual message-body.
     *
     * Or an empty array when there are no logs.
     *
     * @param string $start
     * @param string $end
     *
     * @return array[]|array
     */
    public function getLogBetweenCommits($start, $end)
    {
        // First we get all the commits, then of each commit we get the actual data
        // We can't the commit data in one go because the body contains newlines

        $commits = StringUtil::splitLines($this->processHelper->runCommand(
            [
                'git',
                '--no-pager',
                'log',
                '--oneline',
                '--no-color',
                '--format=%H',
                '--reverse',
                $start.'..'.$end,
            ]
        ));

        return array_map(
            function ($commitHash) {
                // 0=author, 1=subject, anything higher then 2 is the full body
                $commitData = StringUtil::splitLines(
                    $this->processHelper->runCommand(
                        [
                            'git',
                            '--no-pager',
                            'show',
                            '--format=%an <%ae>%n%s%n%b',
                            '--no-color',
                            '--no-patch',
                            $commitHash,
                        ]
                    )
                );

                return [
                    'sha' => $commitHash,
                    'author' => array_shift($commitData),
                    'subject' => $commitData[0],
                    // subject + \n\n + {$commitData remaining}
                    'message' => array_shift($commitData)."\n\n".implode("\n", $commitData),
                ];
            },
            $commits
        );
    }

    /**
     * @return string The vendor name
     */
    public function getVendorName()
    {
        $output = $this->processHelper->runCommand('git remote show -n origin', false, null, true);
        $outputLines = StringUtil::splitLines($output);

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
     * @param string $ref commit/branch or HEAD (default is HEAD)
     *
     * @return string The tag name
     */
    public function getLastTagOnBranch($ref = 'HEAD')
    {
        return $this->processHelper->runCommand(['git', 'describe', '--tags', '--abbrev=0', $ref]);
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
                '--no-pager',
                'ls-files',
            ]
        );

        foreach ($options as $name => $value) {
            $builder->setOption($name, $value);
        }

        $process = $builder->getProcess();
        $process->run();

        return StringUtil::splitLines($process->getOutput());
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

    public function createTempBranch($originalBranch)
    {
        $tempBranchName = 'tmp--'.$originalBranch;
        $this->tempBranches[] = $tempBranchName;

        return $tempBranchName;
    }

    public function clearTempBranches()
    {
        foreach ($this->tempBranches as $branch) {
            $this->processHelper->runCommand(['git', 'branch', '-D', $branch], true);
        }
    }

    /**
     * @return RemoteMergeOperation
     */
    public function createRemoteMergeOperation()
    {
        return new RemoteMergeOperation($this, $this->filesystemHelper);
    }

    /**
     * @param string $base          The base branch name
     * @param string $sourceBranch  The source branch name
     * @param string $commitMessage Commit message to use for the merge-commit
     *
     * @return string Thew merge-commit hash
     *
     * @throws WorkingTreeIsNotReady
     */
    public function mergeBranch($base, $sourceBranch, $commitMessage)
    {
        $this->guardWorkingTreeReady();
        $this->stashBranchName();

        $tmpName = $this->filesystemHelper->newTempFilename();
        file_put_contents($tmpName, $commitMessage);

        $this->checkout($base);

        $this->processHelper->runCommands(
            [
                [
                    'line' => ['git', 'merge', '--no-ff', '--no-commit', $sourceBranch],
                    'allow_failures' => false,
                ],
                [
                    'line' => ['git', 'commit', '-F', $tmpName],
                    'allow_failures' => false,
                ],
            ]
        );

        return trim($this->processHelper->runCommand('git rev-parse HEAD'));
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

    public function pushToRemote($remote, $ref, $setUpstream = false, $force = false)
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
        $this->stashBranchName();

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
            $this->restoreStashedBranch();

            throw new \RuntimeException('Git rebase failed to switch base.', 0, $e);
        }
    }

    /**
     * @param string $base
     * @param string $branchName
     * @param bool   $ignoreMultipleAuthors Ignore there are multiple authors (ake force)
     *
     * @throws WorkingTreeIsNotReady
     * @throws CannotSquashMultipleAuthors
     */
    public function squashCommits($base, $branchName, $ignoreMultipleAuthors = false)
    {
        $this->guardWorkingTreeReady();
        $this->stashBranchName();

        $this->checkout($branchName);

        // Check if there are multiple authors, we only use the e-mail address
        // As the name could have changed (eg. typo's and accents)
        if (!$ignoreMultipleAuthors) {
            $authors = array_unique(
                StringUtil::splitLines(
                    $this->processHelper->runCommand(
                        [
                            'git',
                            '--no-pager',
                            'log',
                            '--oneline',
                            '--no-color',
                            '--format=%ae',
                            $base.'..'.$branchName,
                        ]
                    )
                )
            );

            if (count($authors) > 1) {
                throw new CannotSquashMultipleAuthors();
            }
        }

        // Get commits only in the branch but not in base (in reverse order)
        // we can't use --max-count here because that is applied before the reversing!
        //
        // using git-log works better then finding the fork-point with git-merge-base
        // because this protects against edge cases were there is no valid fork-point

        $firstCommitHash = StringUtil::splitLines($this->processHelper->runCommand(
            [
                'git',
                '--no-pager',
                'log',
                '--oneline',
                '--no-color',
                '--format=%H',
                '--reverse',
                $base.'..'.$branchName,
            ]
        ))[0];

        // 0=author anything higher then 0 is the full body
        $commitData = StringUtil::splitLines(
            $this->processHelper->runCommand(
                [
                    'git',
                    '--no-pager',
                    'show',
                    '--format=%an <%ae>%n%s%n%n%b',
                    '--no-color',
                    '--no-patch',
                    $firstCommitHash,
                ]
            )
        );

        $author = array_shift($commitData);
        $message = implode("\n", $commitData);

        $this->reset($base);
        $this->commit(
            $message,
            [
                'a',
                '-author' => $author,
            ]
        );
    }

    public function syncWithRemote($remote, $branchName = null)
    {
        $this->guardWorkingTreeReady();

        $activeBranchName = $this->getActiveBranchName($branchName);
        $this->stashBranchName();

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

    /**
     * Stashes the active branch-name.
     *
     * This will only stash the branch-name when no other branch was active
     * already.
     */
    public function stashBranchName()
    {
        $activeBranch = $this->getActiveBranchName('HEAD');

        if (null === $this->stashedBranch && 'HEAD' !== $activeBranch) {
            $this->stashedBranch = $activeBranch;
        }
    }

    private function guardWorkingTreeReady()
    {
        if (!$this->isWorkingTreeReady()) {
            throw new WorkingTreeIsNotReady();
        }
    }
}
