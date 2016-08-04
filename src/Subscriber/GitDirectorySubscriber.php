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

use Gush\Command\BaseCommand;
use Gush\Event\GushEvents;
use Gush\Exception\UserException;
use Gush\Feature\GitDirectoryFeature;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GitDirectorySubscriber implements EventSubscriberInterface
{
    private $gitHelper;

    public function __construct(GitHelper $gitHelper)
    {
        $this->gitHelper = $gitHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            GushEvents::INITIALIZE => 'initialize',
        ];
    }

    public function initialize(ConsoleCommandEvent $event)
    {
        /** @var GitDirectoryFeature|BaseCommand $command */
        $command = $event->getCommand();

        if (!$command instanceof GitDirectoryFeature) {
            return;
        }

        if (!$this->gitHelper->isGitDir()) {
            throw new UserException(
                sprintf(
                    'The "%s" command can only be executed from the root of a Git repository.',
                    $command->getName()
                )
            );
        }
    }
}
