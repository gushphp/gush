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
use ManagerTools\Exception\FileNotFoundException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Yaml\Yaml;

class Application extends BaseApplication
{
    /**
     * @var array Array of paratemers
     */
    protected $parameters;
    /**
     * @var \Github\Client The Github Client
     */
    protected $githubClient;

    public function __construct()
    {
        $this->readParameters();
        $this->buildGithubClient();

        parent::__construct();
    }

    /**
     * @param  mixed $key
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->parameters[$key];
    }

    /**
     * @return \Github\Client
     */
    public function getGithubClient()
    {
        return $this->githubClient;
    }

    /**
     * @throws \ManagerTools\Exception\FileNotFoundException
     */
    private function readParameters()
    {
        $filename = getcwd().'/.manager-tools.yml';

        if (!file_exists($filename)) {
            throw new FileNotFoundException(
                'The \'.manager-tools.yml\' doest not exist, please configure it.'
            );
        }

        $yaml = new Yaml();
        $parsed = $yaml->parse($filename);
        $this->parameters = $parsed['parameters'];
    }

    /**
     * Creates the Github Client and authenticates the user for future requests
     *
     * @throws \RuntimeException
     */
    private function buildGithubClient()
    {
        $cacheFolder = $this->parameters['github.cache_folder'];

        if (!file_exists($cacheFolder)) {
            throw new \RuntimeException(
                sprintf('The cache folder \'%s\' does not exist. Please create it.', $cacheFolder)
            );
        }

        if (!is_writable($cacheFolder)) {
            throw new \RuntimeException(
                sprintf('The cache folder \'%s\' is not writable. Please change it\'s permissions', $cacheFolder)
            );
        }

        $cachedClient = new CachedHttpClient(array(
            'cache_dir' => $cacheFolder
        ));

        $this->githubClient = new Client($cachedClient);
        $this->githubClient->authenticate(
            $this->parameters['github.username'],
            $this->parameters['github.password'],
            'http_password'
        );
    }
}
