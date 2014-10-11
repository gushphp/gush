<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Subscriber;

use Gush\Helper\FilesystemHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandEndSubscriber implements EventSubscriberInterface
{

    /** @var FilesystemHelper */
    private $gitHelper;

    public function __construct(FilesystemHelper $filesystemHelper)
    {
        $this->gitHelper = $filesystemHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::TERMINATE => 'cleanUpTempFiles',
        ];
    }

    public function cleanUpTempFiles()
    {
        $this->gitHelper->clearTempFiles();
    }
}
