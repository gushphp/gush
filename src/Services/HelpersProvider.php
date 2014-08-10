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

use Gush\Helper as GushHelper;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class HelpersProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['helpers.autocomplete'] = function ($c) {
            return new GushHelper\AutocompleteHelper();
        };

        $pimple['helpers.editor'] = function ($c) {
            return new GushHelper\EditorHelper();
        };

        $pimple['helpers.git'] = function ($c) {
            return new GushHelper\GitHelper($c['helpers.process']);
        };

        $pimple['helpers.git_repo'] = function ($c) {
            return new GushHelper\GitRepoHelper();
        };

        $pimple['helpers.meta'] = function ($c) {
            return new GushHelper\MetaHelper($c['meta.supported_meta_files']);
        };

        $pimple['helpers.process'] = function ($c) {
            return new GushHelper\ProcessHelper();
        };

        $pimple['helpers.table'] = function ($c) {
            return new GushHelper\TableHelper();
        };

        $pimple['helpers.template'] = function ($c) {
            return new GushHelper\TemplateHelper($c['symfony.helpers.question'], $c['application']);
        };

        $pimple['helpers.text'] = function ($c) {
            return new GushHelper\TextHelper();
        };
    }
}
