<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Subscriber;

use Gush\Feature\TableFeature;
use Symfony\Component\Console\Command\Command;

class TestTableCommand extends Command implements TableFeature
{
    public function getTableDefaultLayout()
    {
        return 'default';
    }
}
