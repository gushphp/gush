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

final class CannotSquashMultipleAuthors extends \Exception
{
    public function __construct()
    {
        parent::__construct('Unable to squash the commits when there are multiple authors found.');
    }
}
