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

use Ddd\Slug\Infra\SlugGenerator\DefaultSlugGenerator;
use Ddd\Slug\Infra\Transliterator\LatinTransliterator;
use Ddd\Slug\Infra\Transliterator\TransliteratorCollection;
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

    protected $enum = [];

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
        $config = $this->getApplication()->getConfig();

        return $config->get($key);
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
     * @todo Move this to TextHelper
     * @return DefaultSlugGenerator
     */
    protected function getSlugifier()
    {
        return new DefaultSlugGenerator(
            new TransliteratorCollection(
                [new LatinTransliterator()]
            ),
            []
        );
    }

    /**
     * Return a description for an enumerated value.
     *
     * @param string $name - name of enumerated value.
     *
     * @return string
     */
    protected function formatEnumDescription($name)
    {
        return 'One of <comment>' . implode('</comment>, <comment>', $this->enum[$name]) . '</comment>';
    }

    /**
     * Check to see if the given value is contained in the named
     * enum definition.
     *
     * @param string $name  - name of key in $this->enum array
     * @param string $value - value to validate
     *
     * @throws \InvalidArgumentException
     */
    protected function validateEnum($name, $value)
    {
        if (!isset($this->enum[$name])) {
            throw new \InvalidArgumentException('Unknown enum ' . $name);
        }

        if (!in_array($value, $this->enum[$name])) {
            throw new \InvalidArgumentException(
                'Value must be one of ' . implode(', ', $this->enum[$name]) . ' got "' . $value . '"'
            );
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

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->getDispatcher()->dispatch(GushEvents::INITIALIZE, new ConsoleEvent($this, $input, $output));
    }
}
