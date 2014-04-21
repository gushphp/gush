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
use Gush\Exception\AdapterException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a base class for adapting Gush to use different providers.
 * E.g. Github, Gitlab, Bitbucket
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Luis Cordova <cordoval@gmail.com>
 */
interface Adapter
{
    /**
     * @param Config $configuration Configuration for the adapter
     */
    public function __construct(Config $configuration);

    /**
     * Runs the configuration and returns the values as an array
     *
     * @param OutputInterface $output
     * @param DialogHelper    $dialog
     *
     * @return array
     */
    public static function doConfiguration(OutputInterface $output, DialogHelper $dialog);

    /**
     * Returns the name of the adapter
     *
     * @throws AdapterException
     * @return string
     */
    public function getName();

    /**
     * Authenticates the Adapter
     *
     * @return bool
     */
    public function authenticate();

    /**
     * Returns true if the adapter is authenticated, false otherwise
     *
     * @return bool
     */
    public function isAuthenticated();

    /**
     * Returns the URL for generating a token.
     * If the adapter does not support tokens, returns null
     *
     * @return null|string
     */
    public function getTokenGenerationUrl();

    /**
     * Creates a fork from upstream and returns an array
     * with the forked url e.g. git@github.com:cordoval/repoName.git
     *
     * @param string $org
     *
     * @return array
     */
    public function createFork($org);

    /**
     * @param string $subject
     * @param string $body
     * @param array  $options
     *
     * @return mixed
     */
    public function openIssue($subject, $body, array $options = []);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getIssue($id);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getIssueUrl($id);

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    public function getIssues(array $parameters = []);

    /**
     * @param int $id
     * @param array   $parameters
     *
     * @return mixed
     */
    public function updateIssue($id, array $parameters);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function closeIssue($id);

    /**
     * @param int $id
     * @param string  $message
     *
     * @return mixed
     */
    public function createComment($id, $message);

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getComments($id);

    /**
     * @return mixed
     */
    public function getLabels();

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    public function getMilestones(array $parameters = []);

    /**
     * @param string $base
     * @param string $head
     * @param string $subject
     * @param string $body
     * @param array  $parameters
     *
     * @return mixed
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = []);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getPullRequest($id);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getPullRequestCommits($id);

    /**
     * @param int    $id
     * @param string $message
     *
     * @return mixed
     */
    public function mergePullRequest($id, $message);

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return string
     */
    public function createRelease($name, array $parameters = []);

    /**
     * @return mixed
     */
    public function getReleases();

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function removeRelease($id);

    /**
     * @param int    $id
     * @param string $name
     * @param string $contentType
     * @param string $content
     *
     * @return mixed
     */
    public function createReleaseAssets($id, $name, $contentType, $content);
}
