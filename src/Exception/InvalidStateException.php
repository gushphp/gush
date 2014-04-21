<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Exception;

/**
 * Exception for an invalid state
 *
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class InvalidStateException extends \RuntimeException
{
    /**
     * @param string $state
     * @param array  $validStates
     */
    public function __construct($state, array $validStates = [])
    {
        $message = sprintf('The state "%s" is invalid.', $state);

        if (!empty($validStates)) {
            $message .= sprintf('Valid states is "%s"', implode('", "', $validStates));
        }

        parent::__construct($message);
    }
}
