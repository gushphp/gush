<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Gush\Config;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class BaseAdapter implements Adapter
{
    const NAME = 'unknown';

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
    public function getName()
    {
        return self::NAME;
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

    /**
     * {@inheritdoc}
     */
    abstract public function authenticate();

    /**
     * {@inheritdoc}
     */
    abstract public function isAuthenticated();

    /**
     * {@inheritdoc}
     */
    abstract public function getTokenGenerationUrl();

    /**
     * {@inheritdoc}
     */
    abstract public function createFork($org);

    /**
     * {@inheritdoc}
     */
    abstract public function openIssue($subject, $body, array $options = []);

    /**
     * {@inheritdoc}
     */
    abstract public function getIssue($id);

    /**
     * {@inheritdoc}
     */
    abstract public function getIssueUrl($id);

    /**
     * {@inheritdoc}
     */
    abstract public function getIssues(array $parameters = []);

    /**
     * {@inheritdoc}
     */
    abstract public function updateIssue($id, array $parameters);

    /**
     * {@inheritdoc}
     */
    abstract public function closeIssue($id);

    /**
     * {@inheritdoc}
     */
    abstract public function createComment($id, $message);

    /**
     * {@inheritdoc}
     */
    abstract public function getComments($id);

    /**
     * {@inheritdoc}
     */
    abstract public function getLabels();

    /**
     * {@inheritdoc}
     */
    abstract public function getMilestones(array $parameters = []);

    /**
     * {@inheritdoc}
     */
    abstract public function openPullRequest($base, $head, $subject, $body, array $parameters = []);

    /**
     * {@inheritdoc}
     */
    abstract public function getPullRequest($id);

    /**
     * {@inheritdoc}
     */
    abstract public function getPullRequestUrl($id);

    /**
     * {@inheritdoc}
     */
    abstract public function getPullRequestCommits($id);

    /**
     * {@inheritdoc}
     */
    abstract public function mergePullRequest($id, $message);

    /**
     * {@inheritdoc}
     */
    abstract public function createRelease($name, array $parameters = []);

    /**
     * {@inheritdoc}
     */
    abstract public function getReleases();

    /**
     * {@inheritdoc}
     */
    abstract public function removeRelease($id);

    /**
     * {@inheritdoc}
     */
    abstract public function createReleaseAssets($id, $name, $contentType, $content);
}
