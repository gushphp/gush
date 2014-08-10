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
use Gush\Meta\Base;
use Gush\Meta\Twig;

class MetaProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['meta.supported_meta_files'] = function ($c) {
            return [
                'php'  => new Base(),
                'js'   => new Base(),
                'css'  => new Base(),
                'twig' => new Twig(),
            ];
        };
    }
}