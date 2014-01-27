<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Subscriber;

use Gush\Event\CommandEvent;
use Gush\Event\GushEvents;
use Gush\Feature\GitHubFeature;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GitHubSubscriber implements EventSubscriberInterface
{
    private $gitHelper;

    public function __construct(GitHelper $gitHelper)
    {
        $this->gitHelper = $gitHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            GushEvents::DECORATE_DEFINITION => 'decorateDefinition',
        ];
    }

    public function decorateDefinition(CommandEvent $event)
    {
        $command = $event->getCommand();

        if (!$command instanceof GitHubFeature) {
            return;
        }

        $command
            ->addOption(
                'org',
                'o',
                InputOption::VALUE_REQUIRED,
                'Name of the GitHub organization',
                $this->gitHelper->getVendorName()
            )
            ->addOption(
                'repo',
                'r',
                InputOption::VALUE_REQUIRED,
                'Name of the GitHub repository',
                $this->gitHelper->getRepoName()
            )
        ;
    }
}
