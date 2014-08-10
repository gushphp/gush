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

use Gush\Helper\AutocompleteHelper;
use Gush\Helper\EditorHelper;
use Gush\Helper\GitHelper;
use Gush\Helper\GitRepoHelper;
use Gush\Helper\MetaHelper;
use Gush\Helper\ProcessHelper;
use Gush\Helper\TableHelper;
use Gush\Helper\TemplateHelper;
use Gush\Helper\TextHelper;
use Pimple\Container;

class HelpersProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['helpers.autocomplete'] = function ($c) {
            return new AutocompleteHelper();
        };

        $pimple['helpers.editor'] = function ($c) {
            return new EditorHelper();
        };

        $pimple['helpers.git'] = function ($c) {
            return new GitHelper($c['helpers.process']);
        };

        $pimple['helpers.git_repo'] = function ($c) {
            return new GitRepoHelper();
        };

        $pimple['helpers.meta'] = function ($c) {
            return new MetaHelper($c['meta.supported_meta_files']);
        };

        $pimple['helpers.process'] = function ($c) {
            return new ProcessHelper();
        };

        $pimple['helpers.table'] = function ($c) {
            return new TableHelper();
        };

        $pimple['helpers.template'] = function ($c) {
            return new TemplateHelper($c['symfony.helpers.question'], $c['application']);
        };

        $pimple['helpers.text'] = function ($c) {
            return new TextHelper();
        };
    }
}
