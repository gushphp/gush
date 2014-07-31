<?php

namespace Gush;

use Gush\Services\HelpersProvider;
use Gush\Services\MetaProvider;
use Pimple\Container as BaseContainer;

class Container extends BaseContainer
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        $this->register(new HelpersProvider());
        $this->register(new MetaProvider());
    }
}