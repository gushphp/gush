<?php

/**
 * PhpStorm.
 */

namespace Gush\Tester\Adapter;

use Gush\Config;
use Gush\Factory\IssueTrackerFactory;
use Gush\Factory\RepositoryManagerFactory;
use Symfony\Component\Console\Helper\HelperSet;

final class TestAdapterFactory implements RepositoryManagerFactory, IssueTrackerFactory
{
    public function createConfigurator(HelperSet $helperSet)
    {
        return new TestConfigurator(
            'GitHub',
            'https://api.github.com/',
            'https://github.com'
        );
    }

    public function createIssueTracker(array $adapterConfig, Config $config)
    {
        return new TestAdapter();
    }

    public function createRepositoryManager(array $adapterConfig, Config $config)
    {
        return new TestAdapter();
    }
}
