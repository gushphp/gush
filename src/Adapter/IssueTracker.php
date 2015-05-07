<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Gush\Exception\AdapterException;

/**
 * IssueTracker is the interface implemented by all Gush IssueTracker classes.
 *
 * Note that each IssueTracker instance can be only used once per issue tracker system.
 */
interface IssueTracker
{
    /**
     * Authenticates the tracker.
     *
     * @return bool
     */
    public function authenticate();

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
     * "pull_request": false
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
     * @param int   $page
     * @param int   $perPage
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
     * @throws AdapterException when updating of the issue failed (eg. disabled or not authorized)
     */
    public function updateIssue($id, array $parameters);

    /**
     * Closes an issue by id.
     *
     * @param int $id
     *
     * @return void
     *
     * @throws AdapterException when closing of the issue failed (eg. disabled or not authorized)
     */
    public function closeIssue($id);

    /**
     * Creates a new a comment on an issue.
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
     * Gets comments of an issue.
     *
     * Returned value must be an array with the following data per entry (values are by example).
     * If a value is not supported null must be used instead.
     *
     * "id":         1
     * "url":        "https://github.com/octocat/Hello-World/issues/1347#issuecomment-1"
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
}
