<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Process\Process;

/**
 * Helper for launching shell commands
 */
class ProcessHelper extends Helper
{
    public function getName()
    {
        return 'process';
    }

    /**
     * Run a command through the ProcessBuilder
     *
     * @param  array             $command
     * @param  Boolean           $allowFailures
     * @throws \RuntimeException
     */
    public function runCommand(array $command, $allowFailures = false)
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
     * Run a series of shell command through a Process
     *
     * @param array $commands
     */
    public function runCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->runCommand(explode(' ', $command['line']), $command['allow_failures']);
        }
    }
}
