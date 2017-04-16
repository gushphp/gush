<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Helper for launching shell commands.
 */
class ProcessHelper extends Helper implements OutputAwareInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'process';
    }

    /**
     * Run a command through the ProcessBuilder.
     *
     * @param string|array $command
     * @param bool         $allowFailures
     * @param \Closure     $callback Callback for Process (e.g. for logging output in realtime)
     *
     * @return string
     */
    public function runCommand($command, $allowFailures = false, $callback = null)
    {
        if (is_string($command)) {
            $command = $this->parseProcessArguments($command);
        }

        $builder = new ProcessBuilder($command);
        $builder
            ->setWorkingDirectory(getcwd())
            ->setTimeout(3600)
        ;
        $process = $builder->getProcess();

        $remover = function ($untrimmed) {
            $removedSingleQuotes = ltrim(rtrim($untrimmed, "'"), "'");
            $removedDoubleuotes = ltrim(rtrim($removedSingleQuotes, '"'), '"');

            return $removedDoubleuotes;
        };
        if ($this->output instanceof OutputInterface) {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $commandLine = implode(' ', array_map($remover, explode(' ', $process->getCommandLine())));
                $this->output->writeln('<question>CMD</question> '.$commandLine);
            }
        }

        $process->run($callback);

        if (!$process->isSuccessful() && !$allowFailures) {
            throw new \RuntimeException(trim($process->getErrorOutput()), (int) $process->getExitCode());
        }

        return trim($process->getOutput());
    }

    /**
     * @param string|string[] $command
     *
     * @return ProcessBuilder
     */
    public function getProcessBuilder($command)
    {
        if (is_string($command)) {
            $command = $this->parseProcessArguments($command);
        }

        $builder = new ProcessBuilder($command);
        $builder
            ->setWorkingDirectory(getcwd())
            ->setTimeout(3600)
        ;

        return $builder;
    }

    /**
     * Run a series of shell command through a Process.
     *
     * @param array $commands
     */
    public function runCommands(array $commands)
    {
        $output = $this->output;

        $callback = function ($type, $buffer) use ($output) {
            if (Process::ERR === $type) {
                $output->write('<info>OUT</info> '.$buffer);
            } else {
                $output->write('<comment>OUT</comment> '.$buffer);
            }
        };

        foreach ($commands as $command) {
            $this->runCommand($command['line'], $command['allow_failures'], $callback);
        }
    }

    /**
     * @throws \RuntimeException
     *
     * @return string
     */
    public function probePhpCsFixer()
    {
        $execFinder = new ExecutableFinder();
        $execFinder->setSuffixes(['.bat', '.cmd', '.sh', '']);

        $fixer = $execFinder->find('php-cs-fixer', 'php-cs-fixer');

        $builder = new ProcessBuilder([$fixer, '--version']);
        $builder
            ->setWorkingDirectory(getcwd())
            ->setTimeout(3600)
        ;

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Please install php-cs-fixer');
        }

        return $fixer;
    }

    /**
     * @param string $command
     *
     * @throws \InvalidArgumentException
     *
     * @return string[]
     */
    protected function parseProcessArguments($command)
    {
        if (preg_match_all('/((?:"(?:(?:[^"\\\\]|\\\\.)+)")|(?:\'(?:[^\'\\\\]|\\\\.)+\')|[^ ]+)/i', $command, $args)) {
            $normalizeCommandArgument = function ($argument) {
                if ("'" === $argument[0] || '"' === $argument[0]) {
                    $quote = $argument[0];

                    $argument = substr($argument, 1, -1);
                    $argument = str_replace('\\'.$quote, $quote, $argument);
                    $argument = str_replace('\\\\', '\\', $argument);
                }

                return $argument;
            };

            return array_map($normalizeCommandArgument, $args[0]);
        }

        throw new \InvalidArgumentException(sprintf('Unable to parse command "%s".', $command));
    }
}
