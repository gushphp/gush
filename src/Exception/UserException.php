<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Exception;

class UserException extends \Exception
{
    private $messages = [];

    /**
     * @param string|array $message
     * @param int          $code
     * @param \Exception   $previous
     */
    public function __construct($message, $code = 1, \Exception $previous = null)
    {
        $this->messages = (array) $message;

        parent::__construct(implode("\n", $this->messages), $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
