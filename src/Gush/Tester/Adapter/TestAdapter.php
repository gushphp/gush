<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tester\Adapter;

use Gush\Adapter\BaseAdapter;

/**
 * @author  Aaron Scherer <aequasi@gmail.com>
 */
class TestAdapter extends BaseAdapter
{
    /**
     * @var string
     */
    protected $name = 'test';

    const PULL_REQUEST_NUMBER = 40;

    const ISSUE_NUMBER = 7;

    public function isAuthenticated()
    {
        return true;
    }

    /**
     * Authenticates the Adapter
     *
     * @return Boolean
     */
    public function authenticate()
    {
        return true;
    }

    /**
     * @param string $subject
     * @param string $body
     * @param array  $options
     *
     * @return mixed
     */
    public function openIssue($subject, $body, array $options = [])
    {
        return ['number' => 77];
    }

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function getIssue($id)
    {
        return [
            'number'       => 60,
            'state'        => "open",
            'user'         => ['login' => 'weaverryan'],
            'assignee'     => ['login' => 'cordoval'],
            'pull_request' => [],
            'milestone'    => ['title' => "Conquer the world"],
            'labels'       => [['name' => 'actionable'], ['name' => 'easy pick']],
            'title'        => 'Write a behat test to launch strategy',
            'body'         => 'Help me conquer the world. Teach them to use gush.',
        ];
    }

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function getIssueUrl($id)
    {
        return 'https://github.com/cordoval/gush/issues/' . $id;
    }

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    public function getIssues(array $parameters = [])
    {
        return [
            [
                'number'     => '1',
                'title'      => 'easy issue',
                'body'       => 'this issue is easy',
                'labels'     => [['name' => 'critic'], ['name' => 'easy pick']],
                'state'      => 'open',
                'user'       => ['login' => 'cordoval'],
                'assignee'   => ['login' => 'cordoval'],
                'milestone'  => ['title' => 'some good stuff release'],
                'created_at' => '1969-12-31',
            ],
            [
                'number'     => '2',
                'title'      => 'hard issue',
                'body'       => 'this issue is not so easy',
                'labels'     => [['name' => 'critic']],
                'state'      => 'open',
                'user'       => ['login' => 'weaverryan'],
                'assignee'   => ['login' => 'cordoval'],
                'milestone'  => ['title' => 'some good stuff release'],
                'created_at' => '1969-12-31',
            ],
        ];
    }

    /**
     * @param integer $id
     * @param array   $parameters
     *
     * @return mixed
     */
    public function updateIssue($id, array $parameters)
    {
    }

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function closeIssue($id)
    {
    }

    /**
     * @param integer $id
     * @param string  $message
     *
     * @return mixed
     *
     */
    public function createComment($id, $message)
    {
        return [
            'number' => self::PULL_REQUEST_NUMBER,
        ];
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getComments($id)
    {
    }

    /**
     * @return mixed
     */
    public function getLabels()
    {
        return [
            [
                'url'   => 'https://api.github.com/repos/cordoval/gush/labels/bug',
                'name'  => 'bug',
                'color' => 'f29513'
            ],
        ];
    }

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    public function getMilestones(array $parameters = [])
    {
        return [
            [
                'title' => 'version 1.0'
            ],
        ];
    }

    /**
     * @param string $base
     * @param string $head
     * @param string $subject
     * @param string $body
     * @param array  $parameters
     *
     * @return mixed
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        return ['html_url' => 'http://github.com/cordoval/gush/pull/' . self::PULL_REQUEST_NUMBER];
    }

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function getPullRequest($id)
    {
        return [
            'number'       => self::PULL_REQUEST_NUMBER,
            'state'        => "open",
            'user'         => ['login' => 'weaverryan'],
            'assignee'     => ['login' => 'cordoval'],
            'pull_request' => [],
            'milestone'    => ['title' => "Conquer the world"],
            'labels'       => [['name' => 'actionable'], ['name' => 'easy pick']],
            'title'        => 'Write a behat test to launch strategy',
            'body'         => 'Help me conquer the world. Teach them to use gush.',
            'base'         => ['label' => 'master', 'ref' => 'base_ref'],
            'head'         => ['ref' => 'head_ref']
        ];
    }

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function getPullRequestUrl($id)
    {
        return 'https://github.com/cordoval/gush/pull/' . $id;
    }

    /**
     * @param integer $id
     *
     * @return mixed
     */
    public function getPullRequestCommits($id)
    {
        return [
            [
                'sha'    => '32fe234332fe234332fe234332fe234332fe2343',
                'commit' => ['message' => 'added merge pull request feature'],
                'author' => ['login' => 'cordoval']
            ],
            [
                'sha'    => 'ab34567812345678123456781234567812345678',
                'commit' => ['message' => 'added final touches'],
                'author' => ['login' => 'cordoval']
            ],
        ];
    }

    /**
     * @param $id
     * @param $message
     *
     * @return mixed
     */
    public function mergePullRequest($id, $message)
    {
        return [
            'merged'  => true,
            'message' => 'Pull Request successfully merged.',
        ];
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function createRelease($name, array $parameters = [])
    {
    }

    /**
     * @return mixed
     */
    public function getReleases()
    {
        return [
            [
                'id'               => '123',
                'name'             => 'This is a Release',
                'tag_name'         => 'Tag name',
                'target_commitish' => '123123',
                'draft'            => true,
                'prerelease'       => 'yes',
                'created_at'       => '2014-01-05',
                'published_at'     => '2014-01-05',
            ],
        ];
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function removeRelease($id)
    {
    }

    /**
     * @param integer $id
     * @param string  $name
     * @param string  $contentType
     * @param string  $content
     *
     * @return mixed
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
    }
}
