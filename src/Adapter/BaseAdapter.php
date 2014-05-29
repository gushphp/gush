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

/**
 * Provides a base class for adapting Gush to use different providers.
 * E.g. Github, GitLab, Bitbucket
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
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
    public function supportsRepository($remoteUrl)
    {
        // always returns false as its not save to determine this by default
        return false;
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

    /**
     * @param string $repository
     *
     * @return $this
     */
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

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
        // noop
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseAssets($id)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
        // noop
    }
}
