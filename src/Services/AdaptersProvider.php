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

class AdaptersProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['adapters'] = [
            'github' => 'Gush\Adapter\GitHubFactory',
            'github_enterprise' => 'Gush\Adapter\GitHubEnterpriseFactory',
            'bitbucket' => 'Gush\Adapter\BitbucketFactory',
            'gitlab' => 'Gush\Adapter\GitLabFactory',
        ];
    }
}
