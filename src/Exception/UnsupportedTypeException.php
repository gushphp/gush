<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Exception;

class UnsupportedTypeException extends \RuntimeException
{
    /**
     * @param string $type
     * @param array  $supported
     */
    public function __construct($type, array $supported = [])
    {
        $message = sprintf('The type "%s" is unsupported.', $type);

        if (!empty($supported)) {
            $message .= sprintf(' The supported types are: "%s"', implode('", "', $supported));
        }

        parent::__construct($message);
    }
}
