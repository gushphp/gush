<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Gush\Adapter\Adapter;
use Gush\Event\GushEvents;
use Gush\Template\Messages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class BaseCommand extends Command
{
    const COMMAND_SUCCESS = 1;
    const COMMAND_FAILURE = 0;

    /**
     * Gets the current adapter
     *
     * @return Adapter
     */
    public function getAdapter()
    {
        return $this->getApplication()->getAdapter();
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
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->getDispatcher()->dispatch(
            GushEvents::INITIALIZE,
            new ConsoleEvent($this, $input, $output)
        );
    }
}
