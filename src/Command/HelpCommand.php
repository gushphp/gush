<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Gush\Event\GushEvents;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand as BaseHelpCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelpCommand extends BaseHelpCommand
{
    /**
     * @var Command
     */
    protected $command;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->command) {
            $this->command = $this->getApplication()->find($input->getArgument('command_name'));
        }

        $this->updateCommandDefinition($this->command, $output);

        parent::execute($input, $output);
    }

    private function updateCommandDefinition(Command $command, OutputInterface $output)
    {
        $eventDispatcher = $this->getApplication()->getDispatcher();
        $input = new ArrayInput(['command' => $command->getName()]);

        $event = new ConsoleCommandEvent($command, $input, $output);
        $eventDispatcher->dispatch(GushEvents::DECORATE_DEFINITION, $event);

        $command->getSynopsis(true);
        $command->getSynopsis(false);
        $command->mergeApplicationDefinition();

        try {
            $input->bind($command->getDefinition());
        } catch (\Exception $e) {
            $output->writeln('<error>Something went wrong: </error>'.$e->getMessage());

            return;
        }

        $eventDispatcher->dispatch(GushEvents::INITIALIZE, $event);

        // The options were set on the input but now we need to set them on the Command definition
        if ($options = $input->getOptions()) {
            foreach ($options as $name => $value) {
                $option = $command->getDefinition()->getOption($name);

                if ($option->acceptValue()) {
                    $option->setDefault($value);
                }
            }
        }
    }
}
