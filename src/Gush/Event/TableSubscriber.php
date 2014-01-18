<?php

namespace Gush\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Event\ConsoleEvent;

class TableSubscriber implements EventSubscriberInterface
{
    protected $validLayouts = array(
        'default',
        'compact',
        'borderless',
    );

    public static function getSubscribedEvents()
    {
        return array(
            GushEvents::DECORATE_DEFINITION => 'decorateDefinition',
            GushEvents::INITIALIZE => 'initialize',
        );
    }

    public function decorateDefinition(CommandEvent $event)
    {
        $command = $event->getCommand();
        $command->addOption('table-layout', null, InputOption::VALUE_REQUIRED,
            'Specify the layout for the table, one of default, compact or borderless'
        );
        $command->addOption('table-no-header', null, InputOption::VALUE_NONE,
            'Disable the header on the table'
        );
        $command->addOption('table-no-footer', null, InputOption::VALUE_NONE,
            'Disable the footer on the table'
        );
    }

    public function initialize(ConsoleEvent $event)
    {
        $input = $event->getInput();
        $layout = $input->getOption('table-layout');

        if ($layout) {
            if (!in_array($layout, $this->validLayouts)) {
                throw new \InvalidArgumentException(sprintf(
                    'The table-layout option must be passed one of "%s" was given "%s"',
                    implode(', ', $this->validLayouts),
                    $layout
                ));
            }
        }
    }
}
