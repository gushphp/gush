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

final class RemotePatchOperation
{
    private $gitHelper;
    private $processHelper;
    private $remoteBranch;
    private $remoteName;
    private $tempBranch;
    private $performed = false;

    public function __construct(GitHelper $gitHelper, ProcessHelper $processHelper)
    {
        $this->gitHelper = $gitHelper;
        $this->processHelper = $processHelper;
    }

    public function setRemote($remote, $branch)
    {
        $this->remoteName = $remote;
        $this->remoteBranch = $branch;

        return $this;
    }

    public function pushToRemote()
    {
        if (!$this->performed) {
            throw new \RuntimeException('pushToRemote() can only be called after applyPatch() is performed.');
        }

        $target = trim($this->tempBranch).':'.$this->remoteBranch;

        // Safety guard to prevent deleting a remote branch!!
        if (':' === $target[0]) {
            throw new \RuntimeException(
                sprintf('Push target "%s" does not include the local branch-name, please report this bug!', $target)
            );
        }

        $this->gitHelper->pushToRemote($this->remoteName, $target);
    }

    public function applyPatch($patchFile, $message, $type = 'p0')
    {
        if ($this->performed) {
            throw new \RuntimeException('applyPatch() was already called. Each operation is only usable once.');
        }

        $this->performed = true;

        $this->gitHelper->stashBranchName();
        $this->gitHelper->remoteUpdate($this->remoteName);
        $this->gitHelper->checkout($this->remoteName.'/'.$this->remoteBranch);

        $this->tempBranch = $this->gitHelper->createTempBranch($this->remoteName.'--'.$this->remoteBranch.'-patch');
        $this->gitHelper->checkout($this->tempBranch, true);

        $this->processHelper->runCommand(['patch', '-'.$type, '--input', $patchFile]);
        $this->gitHelper->commit($message, ['a']);

        $this->gitHelper->restoreStashedBranch();
    }
}
