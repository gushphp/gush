<?php

/*
 * This file is part of the Manager Tools.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ManagerTools;

use Github\Client;
use Github\HttpClient\CachedHttpClient;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Yaml\Yaml;

class Application extends BaseApplication
{
    protected $cwd;
    protected $parameters;
    protected $githubClient;

    public function __construct($cwd)
    {
        $this->setCwd($cwd);
        $this->readParameters();
        $this->buildGithubClient();

        parent::__construct();
    }

    public function setCwd($cwd)
    {
        $this->cwd = $cwd;
    }

    public function getCwd()
    {
        return $this->cwd;
    }

    public function getParameter($key)
    {
        return $this->parameters[$key];
    }

    public function getGithubClient()
    {
        return $this->githubClient;
    }

    private function readParameters()
    {
        $yaml = new Yaml();
        $parsed = $yaml->parse($this->getCwd().'/parameters.yml');
        $this->parameters = $parsed['parameters'];
    }

    private function buildGithubClient()
    {
        $cachedClient = new CachedHttpClient(array(
            'cache_dir' => '/tmp/github-api-cache'
        ));

        $this->githubClient = new Client($cachedClient);
        $this->githubClient->authenticate(
            $this->parameters['github.username'],
            $this->parameters['github.password'],
            'http_password'
        );
    }
}