<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Operation;

use Gush\Helper\FilesystemHelper;
use Gush\Helper\GitHelper;
use Gush\Helper\ProcessHelper;

class RemoteMergeOperation
{
    private $gitHelper;
    private $filesystemHelper;
    private $processHelper;

    private $sourceBranch;
    private $sourceRemote;
    private $targetRemote;
    private $targetBranch;
    private $targetBase;
    private $switchBase;
    private $squash = false;
    private $forceSquash = false;
    private $message;
    private $performed = false;
    private $fastForward = false;
    private $withLog = false;
    private $rebase = false;
    private $guardSync = false;

    public function __construct(GitHelper $gitHelper, FilesystemHelper $filesystemHelper, ProcessHelper $processHelper)
    {
        $this->gitHelper = $gitHelper;
        $this->filesystemHelper = $filesystemHelper;
        $this->processHelper = $processHelper;
    }

    public function setSource($remote, $branch)
    {
        $this->sourceRemote = $remote;
        $this->sourceBranch = $branch;

        return $this;
    }

    public function setTarget($remote, $branch)
    {
        $this->targetRemote = $remote;
        $this->targetBranch = $branch;

        return $this;
    }

    public function switchBase($newBase)
    {
        $this->switchBase = $newBase;

        return $this;
    }

    public function squashCommits($squash = true, $ignoreMultipleAuthors = false)
    {
        $this->squash = $squash;
        $this->forceSquash = $ignoreMultipleAuthors;

        return $this;
    }

    public function setMergeMessage($message, $withLog = false)
    {
        $this->message = $message;
        $this->withLog = $withLog;

        return $this;
    }

    public function useFastForward($fastForward = true)
    {
        $this->fastForward = (bool) $fastForward;

        return $this;
    }

    public function performMerge()
    {
        if ($this->performed) {
            throw new \RuntimeException('performMerge() was already called. Each operation is only usable once.');
        }

        $this->performed = true;

        $this->gitHelper->stashBranchName();
        $this->gitHelper->remoteUpdate($this->sourceRemote);
        $this->gitHelper->remoteUpdate($this->targetRemote);

        // To prevent pushing with an outdated base-branch (or pushing commits only existent
        // in the local branch that has the same name as the remote-base) we checkout the
        // "{targetRemote}/{targetBranch}" and create a temp-branch (temp-base).
        //
        // The temp-base equals the target-branch on the remote.
        //
        // Then we merge the temp-source branch into temp-base branch, and push explicitly
        // to the remote base-branch with temp-base branch!
        // In practice: 'git push {targetRemote} {temp-base}:{target-base}' is performed

        $this->createBaseBranch();

        $tempSourceBranch = $this->createSourceBranch();

        if ($this->withLog) {
            $mergeHash = $this->gitHelper->mergeBranchWithLog(
                $this->targetBase,
                $tempSourceBranch,
                $this->message,
                $this->sourceBranch
            );
        } else {
            $mergeHash = $this->gitHelper->mergeBranch(
                $this->targetBase,
                $tempSourceBranch,
                $this->message,
                $this->fastForward
            );
        }

        $this->gitHelper->restoreStashedBranch();

        return $mergeHash;
    }

    public function pushToRemote()
    {
        $target = trim($this->targetBase).':'.$this->targetBranch;

        // Safety guard to prevent deleting a remote base branch!!
        if (':' === $target[0]) {
            throw new \RuntimeException(
                sprintf('Push target "%s" does not include the local branch-name, please report this bug!', $target)
            );
        }

        $this->gitHelper->pushToRemote($this->targetRemote, $target);
    }

    public function rebase(bool $rebase = false)
    {
        $this->rebase = $rebase;

        return $this;
    }

    public function guardSync(bool $guardSync = false)
    {
        $this->guardSync = $guardSync;

        return $this;
    }

    private function createBaseBranch()
    {
        $targetBranch = null !== $this->switchBase ? $this->switchBase : $this->targetBranch;
        $this->targetBase = $this->gitHelper->createTempBranch($this->targetRemote.'--'.$targetBranch);

        $this->gitHelper->checkout($this->targetRemote.'/'.$targetBranch);
        $this->gitHelper->checkout($this->targetBase, true);
    }

    private function createSourceBranch()
    {
        // Create a temp branch for us to work with
        $sourceBranch = $this->gitHelper->createTempBranch($this->sourceRemote.'--'.$this->sourceBranch);

        $this->gitHelper->checkout($this->sourceRemote.'/'.$this->sourceBranch);
        $this->gitHelper->checkout($sourceBranch, true);

        if ($this->switchBase) {
            $this->gitHelper->switchBranchBase(
                $sourceBranch,
                $this->targetRemote.'/'.$this->targetBranch,
                $this->targetBase
            );

            $this->targetBranch = $this->switchBase;
        }

        $currentBaseHeadCommit = $this->processHelper->runCommand(['git', 'rev-parse', $this->targetBase]);
        $lastKnownCommonCommit = $this->processHelper->runCommand(['git', 'merge-base', '--fork-point', $this->targetBase, $sourceBranch]);

        if ($currentBaseHeadCommit !== $lastKnownCommonCommit) {
            if ($this->rebase) {
                try {
                    $this->processHelper->runCommand(['git', 'pull', '--rebase', $this->targetBase]);
                } catch (\Exception $e) {
                    // Error, abort the rebase operation
                    $this->processHelper->runCommand(['git', 'rebase', '--abort'], true);

                    throw new MergeWorkflowException(sprintf('Git rebase failed while trying to synchronize history against "%s".', $this->targetBase), 0, $e);
                }
            } elseif ($this->guardSync) {
                throw new MergeWorkflowException(sprintf('Failed while trying to perform merge against "%s", history is out of sync.', $this->targetBase));
            }
        }

        if ($this->squash) {
            $this->gitHelper->squashCommits($this->targetBase, $sourceBranch, $this->forceSquash);
        }

        // Allow a callback to allow late commits list composition
        if ($this->message instanceof \Closure) {
            $closure = $this->message;
            $this->message = $closure($this->targetBase, $sourceBranch);
        }

        return $sourceBranch;
    }
}
