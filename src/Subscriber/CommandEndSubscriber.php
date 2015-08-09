<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Subscriber;

use Gush\Helper\FilesystemHelper;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The CommandEndSubscriber is activated when the command is terminated.
 *
 * - Clean-up temp files and branches.
 * - Restore the original working branch on exception.
 */
class CommandEndSubscriber implements EventSubscriberInterface
{
    /**
     * @var FilesystemHelper
     */
    private $fsHelper;

    /**
     * @var GitHelper
     */
    private $gitHelper;

    public function __construct(FilesystemHelper $filesystemHelper, GitHelper $gitHelper)
    {
        $this->fsHelper = $filesystemHelper;
        $this->gitHelper = $gitHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::TERMINATE => 'cleanUpTempFiles',
            ConsoleEvents::EXCEPTION => 'restoreStashedBranch',
        ];
    }

    public function cleanUpTempFiles()
    {
        $this->fsHelper->clearTempFiles();
        $this->gitHelper->clearTempBranches();
    }

    public function restoreStashedBranch()
    {
        $this->gitHelper->restoreStashedBranch();
    }
}
