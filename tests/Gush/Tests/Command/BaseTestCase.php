<?php

namespace Gush\Tests\Command;

use Gush\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Gush\Test\HttpClient\TestHttpClient;
use Github\Client;
use Symfony\Component\Console\Command\Command;

class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    protected $httpClient;

    public function setUp()
    {
        $this->httpClient = new TestHttpClient();
    }

    protected function buildGithubClient()
    {
        $githubClient = new Client($this->httpClient);

        return $githubClient;
    }

    protected function getCommandTester(Command $command)
    {
        $application = new Application();
        $application->setGithubClient($this->buildGithubClient());
        $command->setApplication($application);
        $tester = new CommandTester($command);

        return $tester;
    }
}
