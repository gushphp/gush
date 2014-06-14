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

use Gush\Event\CommandEvent;
use Gush\Event\GushEvents;
use Gush\Feature\TemplateFeature;
use Gush\Helper\TemplateHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TemplateSubscriber implements EventSubscriberInterface
{
    /** @var \Gush\Helper\TemplateHelper */
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
                    'Template to use. <info>One of: '.implode('</info>, <info>', $names).'</info>',
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
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The specified template "%s" does not exist, try one of: '.PHP_EOL.' - %s',
                            $template,
                            implode(PHP_EOL.' - ', $validTemplates)
                        )
                    );
                }
            }
        }
    }
}
