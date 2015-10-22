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

/**
 * A rebase operation should only be used by other processes
 * and not directly within commands.
 */
final class RebaseOperation
{
    private $gitHelper;
    private $processHelper;

    private $base;
    private $newBase;
    private $currentBase;
    private $performed;
    private $branch;

    public function __construct(GitHelper $gitHelper, ProcessHelper $processHelper)
    {
        $this->gitHelper = $gitHelper;
        $this->processHelper = $processHelper;
    }

    public function setBase($base)
    {
        $this->base = $base;

        return $this;
    }

    public function onto($newBase, $currentBase, $branch)
    {
        $this->newBase = $newBase;
        $this->currentBase = $currentBase;
        $this->branch = $branch;

        return $this;
    }

    public function performRebase()
    {
        if ($this->performed) {
            throw new \RuntimeException('performRebase() was already called. Each operation is only usable once.');
        }

        try {
            $command = [
                'git',
                'rebase',
            ];

            if ($this->newBase) {
                $command[] = '--onto';
                $command[] = $this->newBase;
                $command[] = $this->base;
                $command[] = $this->branch;
            } else {
                $command[] = $this->base;
            }

            $this->processHelper->runCommand($command);
        } catch (\Exception $e) {
            // Error, abort the rebase process
            $this->processHelper->runCommand(['git', 'rebase', '--abort'], true);

            throw $e;
        }

        $this->performed = true;
    }
}
