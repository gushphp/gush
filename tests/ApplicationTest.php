<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\Application;
use Gush\Tester\HttpClient\TestHttpClient;
use Symfony\Component\Console\Tester\ApplicationTester;
use Gush\Config;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application $application
     */
    protected $application;
    /**
     * @var TestHttpClient $httpClient
     */
    protected $httpClient;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->application = new TestableApplication();
        $this->application->setAutoExit(false);
    }

    /**
     * {@inheritDoc}
     */
    public function testApplicationFirstRun()
    {
        $applicationTester = new ApplicationTester($this->application);
        $applicationTester->run(['command' => 'list']);

        $this->assertRegExp('/Available commands/', $applicationTester->getDisplay());
    }
}
