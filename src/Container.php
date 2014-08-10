<?php

namespace Gush;

use Gush\Services\HelpersProvider;
use Gush\Services\MetaProvider;
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
    }
}
