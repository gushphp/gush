<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Application;
use Gush\Config;
use Gush\Factory\AdapterFactory;
use Gush\Tester\Adapter\TestAdapterFactory;
use Gush\Tester\Adapter\TestIssueTrackerFactory;
use Symfony\Component\Console\Tester\ApplicationTester;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    const GUSH_FILE = <<<EOT
parameters:
    cache-dir: /Users/cordoval/.gush/cache
    adapters: { github: { config: { base_url: 'https://api.github.com/', repo_domain_url: 'https://github.com' }, adapter_class: Gush\Adapter\GitHubAdapter, authentication: { username: cordoval, password-or-token: password, http-auth-type: http_password } } }
    home: /Users/cordoval/.gush
    home_config: /Users/cordoval/.gush/.gush.yml
    local: /Users/cordoval/Sites/gush
    local_config: /Users/cordoval/Sites/gush/.gush.yml
    adapter: github
EOT
    ;

    /**
     * @var Application $application
     */
    protected $application;
    protected $gushFile;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $config = new Config();
        $adapterFactory = new AdapterFactory();
        $adapterFactory->register('github', 'GitHub', new TestAdapterFactory());
        $adapterFactory->register('github_enterprise', 'GitHub Enterprise', new TestAdapterFactory());
        $adapterFactory->register('jira', 'Jira', new TestIssueTrackerFactory());

        $this->application = new TestableApplication($adapterFactory);
        $this->application->setConfig($config);
        $this->application->setAutoExit(false);
    }

    /**
     * @test
     */
    public function first_run_of_the_application()
    {
        $applicationTester = new ApplicationTester($this->application);
        $applicationTester->run(['command' => 'core:configure'], ['interactive' => false]);

        $this->assertRegExp('/Configuration file saved successfully./', $applicationTester->getDisplay(true));
    }
}
