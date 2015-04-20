<?php

/**
 * PhpStorm.
 */

namespace Gush\Tests\Fixtures\Adapter;

use Gush\Config;
use Gush\Factory\IssueTrackerFactory;
use Gush\Factory\RepositoryManagerFactory;
use Symfony\Component\Console\Helper\HelperSet;

final class TestRepoManagerFactory implements RepositoryManagerFactory
{
    public function createConfigurator(HelperSet $helperSet)
    {
        return new TestConfigurator(
            'GitHub',
            'https://api.github.com/',
            'https://github.com'
        );
    }

    public function createRepositoryManager(array $adapterConfig, Config $config)
    {
        return new TestAdapter();
    }
}
