<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Gush\Event\GushEvents;
use Gush\Template\Messages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class BaseCommand extends Command
{
    const COMMAND_SUCCESS = 1;
    const COMMAND_FAILURE = 0;

    /**
     * Gets the Github's Client
     *
     * @return \Github\Client
     */
    protected function getGithubClient()
    {
        return $this->getApplication()->getGithubClient();
    }

    /**
     * Gets a specific parameter
     *
     * @param  mixed $key
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->getApplication()->getConfig()->get($key);
    }

    /**
     * @param  array             $command
     * @param  Boolean           $allowFailures
     * @throws \RuntimeException
     */
    protected function runItem(array $command, $allowFailures = false)
    {
        $builder = new ProcessBuilder($command);
        $builder
            ->setWorkingDirectory(getcwd())
            ->setTimeout(3600)
        ;
        $process = $builder->getProcess();

        $process->run(
            function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo 'ERR > ' . $buffer;
                } else {
                    echo 'OUT > ' . $buffer;
                }
            }
        );

        if (!$process->isSuccessful() && !$allowFailures) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    /**
     * @param array $commands
     */
    protected function runCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->runItem(explode(' ', $command['line']), $command['allow_failures']);
        }
    }

    /**
     * @todo Move this to a CsHelper
     */
    protected function ensurePhpCsFixerInstalled()
    {
        $builder = new ProcessBuilder(['php-cs-fixer']);
        $builder
            ->setWorkingDirectory(getcwd())
            ->setTimeout(3600)
        ;
        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Please install php-cs-fixer');
        }
    }

    protected function render($templateName, array $placeholderValuePairs)
    {
        $resultString = Messages::get($templateName);
        foreach ($placeholderValuePairs as $placeholder => $value) {
            $resultString = str_replace('{{ '.$placeholder.' }}', $value, $resultString);
        }

        return $resultString;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->getDispatcher()->dispatch(
            GushEvents::INITIALIZE,
            new ConsoleEvent($this, $input, $output)
        );
    }
}
