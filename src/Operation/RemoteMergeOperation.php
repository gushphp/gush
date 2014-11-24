<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Operation;


use Gush\Helper\FilesystemHelper;
use Gush\Helper\GitHelper;

class RemoteMergeOperation
{
    private $gitHelper;
    private $filesystemHelper;

    private $sourceBranch;
    private $sourceRemote;
    private $targetRemote;
    private $targetBranch;
    private $switchBase;
    private $squash = false;
    private $forceSquash = false;
    private $message;
    private $performed = false;

    public function __construct(
        GitHelper $gitHelper,
        FilesystemHelper $filesystemHelper
    ) {
        $this->gitHelper = $gitHelper;
        $this->filesystemHelper = $filesystemHelper;
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

    public function setMergeMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function performMerge()
    {
        if ($this->performed) {
            throw new \RuntimeException('performMerge() was already called. Each operation is only usable once.');
        }

        $this->gitHelper->stashBranchName();
        $this->gitHelper->syncWithRemote($this->targetRemote, $this->targetBranch);

        if ($this->switchBase) {
            $this->gitHelper->syncWithRemote($this->targetRemote, $this->switchBase);
        }

        // Create a temp branch for us to work with
        $tempBranchName = $this->gitHelper->createTempBranch($this->sourceRemote.'--'.$this->sourceBranch);
        $this->gitHelper->checkout($this->sourceRemote.'/'.$this->sourceBranch);
        $this->gitHelper->checkout($tempBranchName, true);

        if ($this->switchBase) {
            $this->gitHelper->switchBranchBase($tempBranchName, $this->targetBranch, $this->switchBase);
            $this->targetBranch = $this->switchBase;
        }

        if ($this->squash) {
            $this->gitHelper->squashCommits($this->targetBranch, $tempBranchName, false);
        }

        // Allow a callback to allow late commits list composition
        if ($this->message instanceof \Closure) {
            $closure = $this->message;
            $this->message = $closure($this->targetBranch, $tempBranchName);
        }

        return $this->gitHelper->mergeBranch($this->targetBranch, $tempBranchName, $this->message);
    }

    public function pushToRemote()
    {
        $this->gitHelper->pushToRemote($this->targetRemote, $this->targetBranch);
    }
}
