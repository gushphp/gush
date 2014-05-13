<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Gush\Config;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a base class for adapting Gush to use different providers.
 * E.g. Github, GitLab, Bitbucket
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class BaseAdapter implements Adapter
{
    /**
     * @var Config
     */
    protected $configuration;

    /**
     * @var null|string
     */
    protected $username;

    /**
     * @var null|string
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    public function __construct(Config $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public static function doConfiguration(OutputInterface $output, DialogHelper $dialog)
    {
        return [];
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
