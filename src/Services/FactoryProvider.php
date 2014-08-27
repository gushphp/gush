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

use Gush\Factory\AdapterFactory;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class FactoryProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['factory.adapter'] = function ($c) {
            $adapterFactory = new AdapterFactory();

            foreach ($c['adapters'] as $adapterName => $factoryClass) {
                if (!class_exists($factoryClass)) {
                    continue;
                }

                $adapterFactory->registerAdapter(
                    $adapterName,
                    [$factoryClass, 'createAdapter'],
                    [$factoryClass, 'createAdapterConfigurator']
                );
            }

            foreach ($c['issueTrackers'] as $trackerName => $factoryClass) {
                if (!class_exists($factoryClass)) {
                    continue;
                }

                $adapterFactory->registerIssueTracker(
                    $trackerName,
                    [$factoryClass, 'createIssueTracker'],
                    [$factoryClass, 'createIssueTrackerConfigurator']
                );
            }

            return $adapterFactory;
        };
    }
}
