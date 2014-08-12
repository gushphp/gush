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
use KevinGH\Amend\Helper as UpdateHelper;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Helper\HelperSet;

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

        $pimple['helpers.update'] = function ($c) {
            return new UpdateHelper();
        };

        $pimple['helpers.set'] = function ($c) {
            return new HelperSet([
                $c['symfony.helpers.formatter'],
                $c['symfony.helpers.dialog'],
                $c['symfony.helpers.progress'],
                $c['symfony.helpers.table'],
                $c['symfony.helpers.question'],
                $c['helpers.text'],
                $c['helpers.table'],
                $c['helpers.process'],
                $c['helpers.editor'],
                $c['helpers.git'],
                $c['helpers.template'],
                $c['helpers.meta'],
                $c['helpers.autocomplete'],
                $c['helpers.update'],
            ]);
        };
    }
}
