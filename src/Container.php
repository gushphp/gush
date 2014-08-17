<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

use Gush\Services\AdaptersProvider;
use Gush\Services\AliasProvider;
use Gush\Services\ApplicationProvider;
use Gush\Services\CommandsProvider;
use Gush\Services\FactoryProvider;
use Gush\Services\HelpersProvider;
use Gush\Services\IssueTrackersProvider;
use Gush\Services\MetaProvider;
use Gush\Services\SymfonyEventDispatcherProvider;
use Gush\Services\SymfonyHelpersProvider;
use Pimple\Container as BaseContainer;

class Container extends BaseContainer
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        $this->register(new SymfonyHelpersProvider());
        $this->register(new HelpersProvider());
        $this->register(new MetaProvider());
        $this->register(new AdaptersProvider());
        $this->register(new IssueTrackersProvider());
        $this->register(new FactoryProvider());
        $this->register(new CommandsProvider());
        $this->register(new SymfonyEventDispatcherProvider());
        $this->register(new AliasProvider());
        $this->register(new ApplicationProvider());
    }
}
