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
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Helper as SymfonyHelper;

class SymfonyHelpersProvider implements ServiceProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['symfony.helpers.formatter'] = function ($c) {
            return new SymfonyHelper\FormatterHelper();
        };

        $pimple['symfony.helpers.dialog'] = function ($c) {
            return new SymfonyHelper\DialogHelper();
        };

        $pimple['symfony.helpers.progress'] = function ($c) {
            return new SymfonyHelper\ProgressHelper();
        };

        $pimple['symfony.helpers.table'] = function ($c) {
            return new SymfonyHelper\TableHelper();
        };

        $pimple['symfony.helpers.question'] = function ($c) {
            return new SymfonyHelper\QuestionHelper();
        };
    }
}
