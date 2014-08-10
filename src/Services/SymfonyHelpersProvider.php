<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Services;

use Pimple\Container;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\TableHelper as SymfonyTableHelper;

class SymfonyHelpersProvider implements \Pimple\ServiceProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['symfony.helpers.formatter'] = function ($c) {
            return new FormatterHelper();
        };

        $pimple['symfony.helpers.dialog'] = function ($c) {
            return new DialogHelper();
        };

        $pimple['symfony.helpers.progress'] = function ($c) {
            return new ProgressHelper();
        };

        $pimple['symfony.helpers.table'] = function ($c) {
            return new SymfonyTableHelper();
        };

        $pimple['symfony.helpers.question'] = function ($c) {
            return new QuestionHelper();
        };
    }
}