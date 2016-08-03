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

use Gush\Helper\GitHelper;
use Gush\Helper\ProcessHelper;

class GitSyncOperation
{
    /**
     * Sync by forcing the local branch to equal the remote.
     */
    const SYNC_FORCE = 'force';

    /**
     * Sync by being smart.
     *
     * * If both are up-to-date do nothing.
     * * If a pull is required, do it (using a rebase).
     * * If a push is required, do it.
     */
    const SYNC_SMART = 'smart';

    /**
     * Sync by being smart (but don't use rebase for merging remote changes).
     *
     * * If both are up-to-date do nothing.
     * * If a pull is required, do it (using with a merge).
     * * If a push is required, do it.
     */
    const SYNC_SMART_MERGE = 'smart-merge';

    const DISABLE_PUSH = 1;
    const FORCE_PUSH = 2;

    private $gitHelper;
    private $processHelper;

    private $sourceRemote;
    private $sourceBranch;
    private $localBranch;

    private $destinationRemote;
    private $destinationBranch;

    public function __construct(GitHelper $gitHelper, ProcessHelper $processHelper)
    {
        $this->gitHelper = $gitHelper;
        $this->processHelper = $processHelper;
    }

    public function setLocalRef($branch)
    {
        $this->localBranch = $branch;

        return $this;
    }

    public function setRemoteRef($remote, $branch)
    {
        $this->sourceRemote = $remote;
        $this->sourceBranch = $branch;

        return $this;
    }

    public function setRemoteDestination($remote, $branch)
    {
        $this->destinationRemote = $remote;
        $this->destinationBranch = $branch;

        return $this;
    }

    public function sync($type = self::SYNC_FORCE, $options)
    {
        if ($options & self::DISABLE_PUSH && $options & self::FORCE_PUSH) {
            throw new \InvalidArgumentException('Cannot use both DISABLE_PUSH and FORCE_PUSH.');
        }

        $this->gitHelper->guardWorkingTreeReady();
        $this->gitHelper->stashBranchName();

        $this->gitHelper->remoteUpdate($this->sourceRemote);
        $this->gitHelper->remoteUpdate($this->destinationRemote);

        $this->gitHelper->guardBranchExist($this->localBranch);
        $this->gitHelper->guardRemoteBranchExists($this->sourceRemote, $this->sourceBranch);

        $status = $this->gitHelper->getRemoteDiffStatus($this->sourceRemote, $this->localBranch, $this->sourceBranch);

        // All up-to-date, nothing to do.
        if (GitHelper::STATUS_UP_TO_DATE === $status) {
            return;
        }

        $this->gitHelper->checkout($this->localBranch);

        if (self::SYNC_FORCE === $type) {
            $this->gitHelper->reset($this->sourceRemote.'/'.$this->sourceBranch, 'hard');

            return;
        }

        switch ($status) {
            case GitHelper::STATUS_NEED_PULL:
            case GitHelper::STATUS_DIVERGED:
                if (self::SYNC_SMART === $type) {
                    $this->gitHelper->createRebaseOperation(
                        $this->sourceRemote.'/'.$this->sourceBranch
                    )->performRebase();
                } else {
                    $this->processHelper->runCommand(
                        ['git', 'merge', '--ff', '--no-log', $this->sourceRemote.'/'.$this->sourceBranch]
                    );
                }

                if (GitHelper::STATUS_DIVERGED === $status && !($options & self::DISABLE_PUSH)) {
                    $this->pushToRemote((bool) ($options & self::FORCE_PUSH));
                }
                break;

            case GitHelper::STATUS_NEED_PUSH:
                if (!($options & self::DISABLE_PUSH)) {
                    $this->pushToRemote();
                }
                break;

            default:
                throw new \InvalidArgumentException('Unsupported sync option provided: '.$type);
        }
    }

    private function pushToRemote($force = false)
    {
        $target = trim($this->localBranch).':'.$this->destinationBranch;

        // Safety guard to prevent deleting a remote base branch!!
        if (':' === $target[0]) {
            throw new \RuntimeException(
                sprintf('Push target "%s" does not include the local branch-name, please report this bug!', $target)
            );
        }

        $this->gitHelper->pushToRemote($this->destinationRemote, $target, false, $force);
    }
}
