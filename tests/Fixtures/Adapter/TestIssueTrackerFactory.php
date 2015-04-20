<?php

/**
 * PhpStorm.
 */

namespace Gush\Tests\Fixtures\Adapter;

use Gush\Config;
use Gush\Factory\IssueTrackerFactory;
use Symfony\Component\Console\Helper\HelperSet;

final class TestIssueTrackerFactory implements IssueTrackerFactory
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
        return new TestIssueTracker();
    }
}
