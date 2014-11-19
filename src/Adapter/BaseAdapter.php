<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Gush\Config;
use Gush\Exception\NotImplementedException;

/**
 * Provides a base class for adapting Gush to use different providers.
 * E.g. Github, GitLab, Bitbucket
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
        // always returns false as it is not safe to determine this by default
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
        // no-op
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
        // no-op
    }

    /**
     * {@inheritdoc}
     */
    public function createRepo(
        $name,
        $description,
        $homepage,
        $public = true,
        $organization = null,
        $hasIssues = true,
        $hasWiki = false,
        $hasDownloads = false,
        $teamId = 0,
        $autoInit = true
    ) {
        throw new NotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function switchPullRequestBase($prNumber, $newBase, $newHead, $forceNewPr = false, $comment = null)
    {
        $pr = $this->getPullRequest($prNumber);

        $newPr = $this->openPullRequest(
            $newBase,
            $newHead,
            $pr['title'],
            $pr['body']
        );

        $this->closePullRequest($prNumber);

        return $newPr;
    }
}
