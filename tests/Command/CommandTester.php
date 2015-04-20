<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester as BaseCommandTester;

class CommandTester extends BaseCommandTester
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $input = [], array $options = array())
    {
        $options = array_merge(['decorated' => false], $options);

        return parent::execute($input, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay($normalize = false)
    {
        return parent::getDisplay(true);
    }
}
