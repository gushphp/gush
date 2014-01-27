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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Input\InputOption;
use Gush\Event\GushEvents;
use Gush\Event\CommandEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Gush\Feature\TableFeature;
use Gush\Feature\TemplateFeature;
use Gush\Helper\TemplateHelper;

class TemplateSubscriber implements EventSubscriberInterface
{
    protected $templateHelper;

    public function __construct(TemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            GushEvents::DECORATE_DEFINITION => 'decorateDefinition',
            GushEvents::INITIALIZE => 'initialize',
        ];
    }

    public function decorateDefinition(CommandEvent $event)
    {
        $command = $event->getCommand();

        if ($command instanceof TemplateFeature) {
            $names = $this->templateHelper->getNamesForDomain($command->getTemplateDomain());

            $command
                ->addOption(
                    'template',
                    't',
                    InputOption::VALUE_REQUIRED,
                    'Template to use. <info>One of: ' . implode('</info>, <info>', $names) . '</info>',
                    $command->getTemplateDefault()
                )
            ;
        }
    }

    public function initialize(ConsoleEvent $event)
    {
        $command = $event->getCommand();

        if ($command instanceof TemplateFeature) {
            $input = $event->getInput();
            $template = $input->getOption('template');

            if ($template) {
                $validTemplates = $this->templateHelper->getNamesForDomain(
                    $command->getTemplateDomain()
                );

                if (!in_array($template, $validTemplates)) {
                    throw new \InvalidArgumentException(sprintf(
                        "The specified template \"%s\" does not exist, try one of: \n - %s",
                        $template,
                        implode("\n - ", $validTemplates)
                    ));
                }
            }
        }
    }
}
