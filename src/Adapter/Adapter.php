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
 * Adapter is the interface implemented by all Gush Adapter classes.
 *
 * Note that each adapter instance can be only used for one repository.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface Adapter
{
    /**
     * Constructor.
     *
     * @param Config $configuration Configuration for the adapter
     */
    public function __construct(Config $configuration);

    /**
     * Configures the adapter for usage.
     *
     * This methods is called for building the adapter configuration
     * which will be used every time a command is executed with adapter.
     *
     * @param OutputInterface $output
     * @param DialogHelper    $dialog
     *
     * @return array Validated and normalized configuration as associative array
     *
     * @throws \Exception When any of the validators returns an error
     */
    public static function doConfiguration(OutputInterface $output, DialogHelper $dialog);

    /**
     * Returns the unique name of the adapter.
     *
     * @return string name in lowercase without adapter suffix, eg. 'github'
     */
    public function getName();

    /**
     * Authenticates the Adapter.
     *
     * @return bool
     */
    public function authenticate();

    /**
     * Returns true if the adapter is authenticated, false otherwise.
     *
     * @return bool
     */
    public function isAuthenticated();

    /**
     * Returns the URL for generating a token.
     *
     * If the adapter doesn't support tokens,
     * this will return null instead.
     *
     * @return null|string
     */
    public function getTokenGenerationUrl();

    /**
     * Creates a fork from upstream and returns an array
     * with the forked url e.g. 'git@github.com:cordoval/repoName.git'
     *
     * @param string $org Organisation name
     *
     * @return array An array the with following keys: git_url, html_url
     *
     * @throws AdapterException when creating a fork failed, eg. not authorized or limit reached
     */
    public function createFork($org);

    /**
     * Opens a new issue on the issue tracker.
     *
     * @param string $subject Subject of the issue
     * @param string $body    Body/message of the issue
     * @param array  $options Extra options for the issue
     *
     * @return int issue-id
     *
     * @throws AdapterException when opening of an issue failed (eg. disabled or not authorized)
     */
    public function openIssue($subject, $body, array $options = []);

    /**
     * Gets an issue by id.
     *
     * Returned value must be an array with the following data (values are by example).
     * If a value is not supported null must be used instead.
     *
     * "url":         "https://github.com/octocat/Hello-World/issues/1347"
     * "number":      1347
     * "state":       "open"
     * "title":       "Found a bug"
     * "body":        "I'm having a problem with this."
     * "user":        "username"
     * "labels":      ["bug"]
     * "assignee":    "username"
     * "milestone":   "v1.0"
     * "created_at":  "DateTime Object"
     * "updated_at":  "DateTime Object"
     * "closed_by":   "username"
     *
     * @param int $id
     *
     * @return array
     *
     * @throws AdapterException when issues are disabled for the repository
     *                          or if the issue does not exist (anymore)
     */
    public function getIssue($id);

    /**
     * Gets the web-URL of the issue id.
     *
     * @param int $id
     *
     * @return string ex. "https://github.com/octocat/Hello-World/issues/1347"
     *
     * @throws AdapterException when issues are disabled for the repository
     *                          or if the issue does not exist (anymore)
     */
    public function getIssueUrl($id);

    /**
     * Gets the issues as array.
     *
     * @param array $parameters
     * @param int    $page
     * @param int    $perPage
     *
     * @return array[] An array where each entry has the same structure as described in getIssue()
     *
     * @throws AdapterException when issues are disabled for the repository
     */
    public function getIssues(array $parameters = [], $page = 1, $perPage = 30);

    /**
     * Updates the state of an issue by id.
     *
     * @param int   $id
     * @param array $parameters
     *
     * @return void
     *
     * @throws AdapterException when closing of issue failed (eg. disabled or not authorized)
     */
    public function updateIssue($id, array $parameters);

    /**
     * Closes an issue by id.
     *
     * @param int $id
     *
     * @return void
     *
     * @throws AdapterException when closing of issue failed (eg. disabled or not authorized)
     */
    public function closeIssue($id);

    /**
     * Creates a new a comment on an issue/pull-request.
     *
     * @param int    $id
     * @param string $message
     *
     * @return string|null URL to the comment ex. "https://github.com/octocat/Hello-World/issues/1347#issuecomment-1
     *
     * @throws AdapterException when creating of command failed (eg. disabled or not authorized)
     */
    public function createComment($id, $message);

    /**
     * Gets commands of an issue/pull-request.
     *
     * Returned value must be an array with the following data per entry (values are by example).
     * If a value is not supported null must be used instead.
     *
     * "id":         1
     * "url":        "https://api.github.com/repos/octocat/Hello-World/issues/comments/1"
     * "body":       "Me too"
     * "user":       "username"
     * "created_at": "DateTime Object"
     * "updated_at": "DateTime Object"
     *
     * @param int $id
     *
     * @return array[] [['id' => 1, ...]]
     */
    public function getComments($id);

    /**
     * Gets the supported labels.
     *
     * When the issue tracker does not support labels,
     * this will return an empty array
     *
     * @return string[]
     */
    public function getLabels();

    /**
     * Gets the supported milestones.
     *
     * @param array $parameters
     *
     * @return string[]
     */
    public function getMilestones(array $parameters = []);

    /**
     * Opens a new pull-request.
     *
     * @param string $base
     * @param string $head
     * @param string $subject
     * @param string $body
     * @param array  $parameters
     *
     * @return string URL to pull-request ex. https://github.com/octocat/Hello-World/pull/1
     *
     * @throws AdapterException when the pull request are disabled for the repository
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = []);

    /**
     * Gets the information of a pull-request by id.
     *
     * Returned value must be an array with the following data (values are by example).
     * If a value is not supported null must be used instead.
     *
     * "url":           "https://github.com/octocat/Hello-World/pull/1"
     * "number":        1
     * "state":         "open"
     * "title":         "new-feature"
     * "body":          "Please pull these awesome changes"
     * "created_at":    "DateTime Object"
     * "updated_at":    "DateTime Object"
     * "user":          "username"
     * "merge_commit":  "e5bd3914e2e596debea16f433f57875b5b90bcd6"
     * "merged":        false
     * "merged_by":     "username"
     * "head": [
     *     "ref":   "new-topic"
     *     "sha":   "6dcb09b5b57875f334f61aebed695e2e4193db5e"
     *     "user":  "username"
     *     "repo":  "Hello-World"
     * ]
     * "base": [
     *     "label": "master"
     *     "ref":   "master"
     *     "sha":   "6dcb09b5b57875f334f61aebed695e2e4193db5e"
     *     "repo":  "Hello-World"
     * ]
     *
     * @param int $id
     *
     * @return array
     *
     * @throws AdapterException when pull request are disabled for the repository,
     *                          or if the pull request does not exist (anymore)
     */
    public function getPullRequest($id);

    /**
     * Gets the version-commits of a pull-request.
     *
     * Returned value must be an array with the following data per entry (values are by example).
     * If a value is not supported null must be used instead.
     *
     * 'sha':     '6dcb09b5b57875f334f61aebed695e2e4193db5e'
     * 'message': 'Fix all the bugs'
     * 'user':    'username'
     *
     * @param int $id
     *
     * @return array[] [['sha1' => 'dcb09b5b57875f334f61aebed695e2e4193db5e', ...]]
     *
     * @throws AdapterException when pull request are disabled for the repository,
     *                          or if the pull request does not exist (anymore)
     */
    public function getPullRequestCommits($id);

    /**
     * Merges a pull-request by id.
     *
     * @param int    $id
     * @param string $message
     *
     * @return string sha1 of the merge commit
     *
     * @throws AdapterException when merging failed
     */
    public function mergePullRequest($id, $message);

    /**
     * Gets the pull-requests.
     *
     * Returned value must be an array with the following data per entry (values are by example).
     * If a value is not supported null must be used instead.
     *
     * @param string $state    Only get pull-requests with this state (use getPullRequestStates() supported states)
     * @param int    $page
     * @param int    $perPage
     *
     * @return array[] An array where each entry has the same structure as described in getPullRequest()
     *
     * @throws AdapterException when state is unsupported
     */
    public function getPullRequests($state = null, $page = 1, $perPage = 30);

    /**
     * Get the supported pull-request states.
     *
     * @return string[]
     */
    public function getPullRequestStates();

    /**
     * Creates a new release.
     *
     * For clarity, a release is a tagged version
     * with additional information like a changelog.
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return string Returns the web-URL to the release
     */
    public function createRelease($name, array $parameters = []);

    /**
     * Creates a new release asset.
     *
     * An asset be eg documentation or a full download (library package with vendors).
     * Not every Hub provider supports this however, so implementation is optional.
     *
     * @param int    $id           Id of the release (must exist)
     * @param string $name         Name of the asset (including file extension)
     * @param string $contentType  Mime-type of the asset
     * @param string $content      Actual asset (in raw-binary form without conversion)
     *
     * @return int returns the id of the asset
     */
    public function createReleaseAssets($id, $name, $contentType, $content);

    /**
     * Gets all available created-releases.
     *
     * Returned value must be an array with the following data per entry (values are by example).
     * If a value is not supported null must be used instead.
     *
     * Note. Size is in bytes, url contains link to asset but may not necessary
     * download the actual asset. State can be: "uploaded", "empty", or "uploading".
     *
     * "url":           "https://api.github.com/repos/octocat/Hello-World/releases/assets/1"
     * "id":            1
     * "name":          "example.zip"
     * "label":         "short description"
     * "state":         "uploaded",
     * "content_type":  "application/zip"
     * "size":          1024
     * "created_at":    "DateTime Object"
     * "updated_at":    "DateTime Object"
     * "uploader":      "username"
     *
     * @return array[] [['id' => 1, ...]]
     */
    public function getReleases();

    /**
     * Deletes a release.
     *
     * @param int $id
     *
     * @return void
     *
     * @throws AdapterException when deleting of release failed
     */
    public function removeRelease($id);
}
