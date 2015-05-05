<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Subscriber;

use Gush\Command\BaseCommand;
use Gush\Event\GushEvents;
use Gush\Feature\TableFeature;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TableSubscriber implements EventSubscriberInterface
{
    protected $validLayouts = [
        'default',
        'compact',
        'borderless',
        'github',
    ];

    public static function getSubscribedEvents()
    {
        return [
            GushEvents::DECORATE_DEFINITION => 'decorateDefinition',
            GushEvents::INITIALIZE => 'initialize',
        ];
    }

    public function decorateDefinition(ConsoleCommandEvent $event)
    {
        /** @var TableFeature|BaseCommand $command */
        $command = $event->getCommand();

        if ($command instanceof TableFeature) {
            $command
                ->addOption(
                    'table-layout',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Specify the layout for the table, one of default, compact, borderless, or github',
                    $command->getTableDefaultLayout()
                )
                ->addOption(
                    'table-no-header',
                    null,
                    InputOption::VALUE_NONE,
                    'Disable the header on the table'
                )
                ->addOption(
                    'table-no-footer',
                    null,
                    InputOption::VALUE_NONE,
                    'Disable the footer on the table'
                )
            ;
        }
    }

    public function initialize(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        if (!$command instanceof TableFeature) {
            return;
        }

        $input = $event->getInput();
        $layout = $input->getOption('table-layout');

        if ($layout && !in_array($layout, $this->validLayouts, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The table-layout option must be passed one of "%s" but was given "%s"',
                    implode(', ', $this->validLayouts),
                    $layout
                )
            );
        }
    }
}
