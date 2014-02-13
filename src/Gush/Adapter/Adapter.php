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
use Gush\Exception\AdapterException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a base class for adapting Gush to use different providers.
 * E.g. Github, Gitlab, Bitbucket
 *
 * @author  Aaron Scherer <aequasi@gmail.com>
 */
interface Adapter
{
    /**
     * @param Config      $configuration Configuration for the adapter
     * @param string|null $username
     * @param string|null $repository
     */
    public function __construct(Config $configuration, $username = null, $repository = null);

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
    public static function getName();

    /**
     * Authenticates the Adapter
     *
     * @return Boolean
     */
    public function authenticate();

    /**
     * Returns true if the adapter is authenticated, false otherwise
     *
     * @return Boolean
     */
    public function isAuthenticated();

    /**
     * @param string $subject
     * @param string $body
     * @param array  $options
     *
     * @return mixed
     */
    public function openIssue($subject, $body, array $options = []);

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function getIssue($id);

    /**
     * @param integer $id
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
     * @param integer $id
     * @param array   $parameters
     *
     * @return mixed
     */
    public function updateIssue($id, array $parameters);

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function closeIssue($id);

    /**
     * @param integer $id
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
     * @param integer $id
     *
     * @return mixed
     */
    public function getPullRequest($id);

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function getPullRequestCommits($id);

    /**
     * @param $id
     * @param $message
     *
     * @return mixed
     */
    public function mergePullRequest($id, $message);

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function createRelease($name, array $parameters = []);

    /**
     * @return mixed
     */
    public function getReleases();

    /**
     * @param $id
     *
     * @return mixed
     */
    public function removeRelease($id);

    /**
     * @param integer $id
     * @param string  $name
     * @param string  $contentType
     * @param string  $content
     *
     * @return mixed
     */
    public function createReleaseAssets($id, $name, $contentType, $content);
}
