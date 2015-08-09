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

use Gush\Adapter\Adapter;
use Gush\Adapter\IssueTracker;
use Gush\Config;
use Gush\Event\GushEvents;
use Gush\Template\Messages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    const COMMAND_SUCCESS = 1;
    const COMMAND_FAILURE = 0;

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->getDispatcher()->dispatch(
            GushEvents::DECORATE_DEFINITION,
            new ConsoleCommandEvent($this, $input, $output)
        );

        return parent::run($input, $output);
    }

    /**
     * Gets the current adapter.
     *
     * @return Adapter
     */
    public function getAdapter()
    {
        return $this->getApplication()->getAdapter();
    }

    /**
     * Gets the current adapter.
     *
     * @return IssueTracker
     */
    public function getIssueTracker()
    {
        return $this->getApplication()->getIssueTracker();
    }

    /**
     * Gets the application configuration.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->getApplication()->getConfig();
    }

    /**
     * Gets a specific parameter.
     *
     * @param InputInterface $input
     * @param string         $key
     *
     * @return mixed
     */
    public function getParameter(InputInterface $input, $key)
    {
        $config = $this->getConfig();
        $adapter = $input->getOption('repo-adapter');

        if ($value = $config->get(['adapters', $adapter, $key])) {
            return $value;
        }

        return $config->get($key);
    }

    /**
     * Render a string from the Messages library.
     *
     * @param string $templateName          Name of template
     * @param array  $placeholderValuePairs Associative array of substitute values
     *
     * @return string
     */
    protected function render($templateName, array $placeholderValuePairs)
    {
        $resultString = Messages::get($templateName);
        foreach ($placeholderValuePairs as $placeholder => $value) {
            $resultString = str_replace('{{ '.$placeholder.' }}', $value, $resultString);
        }

        return $resultString;
    }

    /**
     * We override the initialize function as this is the only place
     * where we can dispatch an event which can validate the input when
     * it has been bound with values.
     *
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->getDispatcher()->dispatch(
            GushEvents::INITIALIZE,
            new ConsoleCommandEvent($this, $input, $output)
        );
    }

    protected function appendPlug($outputString)
    {
        return $outputString.PHP_EOL.' Sent using [Gush](https://github.com/gushphp/gush)';
    }
}
