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

use Gush\Application;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ApplicationProvider implements  ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['application.manifesto_url'] = 'http://gushphp.org/manifest.json';

        $pimple['application'] = function ($c) {
            $app = new Application($c['factory.adapter']);
            $app->addCommands($c['commands']);

            return $app;
        };
    }
}
