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

final class UnknownRemoteException extends \RuntimeException
{
    public function __construct($remote)
    {
        parent::__construct(sprintf('The Git remote "%s" is not configured.', $remote));
    }
}
