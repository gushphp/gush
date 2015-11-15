<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Github;

use Gush\Config;
use Gush\Factory\IssueTrackerFactory;
use Gush\Factory\RepositoryManagerFactory;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GitHubFactory implements IssueTrackerFactory, RepositoryManagerFactory
{
    public function createRepositoryManager(array $adapterConfig, Config $config)
    {
        return new GitHubAdapter($adapterConfig, $config);
    }

    public function createIssueTracker(array $adapterConfig, Config $config)
    {
        return new GitHubAdapter($adapterConfig, $config);
    }

    public function createConfigurator(HelperSet $helperSet, Config $config)
    {
        return new GitHubConfigurator(
            $helperSet->get('question'),
            'GitHub issue tracker',
            'https://api.github.com/',
            'https://github.com'
        );
    }
}
