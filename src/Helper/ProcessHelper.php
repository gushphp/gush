<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Helper\Helper;

/**
 * Helper for launching shell commands
 */
class ProcessHelper extends Helper implements OutputAwareInterface
{
    protected $output;

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function getName()
    {
        return 'process';
    }

    /**
     * Run a command through the ProcessBuilder
     *
     * @param array             $command
     * @param Boolean           $allowFailures
     * @param \Closure          Callback for Process (e.g. for logging output in realtime)
     *
     * @return string
     * @throws \RuntimeException
     */
    public function runCommand($command, $allowFailures = false, $callback = null)
    {
        if (is_string($command)) {
            $command = explode(' ', $command);
        }

        $builder = new ProcessBuilder($command);
        $builder
            ->setWorkingDirectory(getcwd())
            ->setTimeout(3600)
        ;
        $process = $builder->getProcess();

        $process->run($callback);

        if (!$process->isSuccessful() && !$allowFailures) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }

    public function getProcessBuilder($arguments)
    {
        $builder = new ProcessBuilder($arguments);
        $builder
            ->setWorkingDirectory(getcwd())
            ->setTimeout(3600)
        ;

        return $builder;
    }

    /**
     * Run a series of shell command through a Process
     *
     * @param array $commands
     */
    public function runCommands(array $commands)
    {
        $output = $this->output;

        $callback = function ($type, $buffer) use ($output) {
            if (Process::ERR === $type) {
                $output->write('<error>ERR ></error> '.$buffer);
            } else {
                $output->write('<comment>OUT ></comment> '.$buffer);
            }
        };

        foreach ($commands as $command) {
            $this->runCommand($command['line'], $command['allow_failures'], $callback);
        }
    }

    public function probePhpCsFixer()
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
}
