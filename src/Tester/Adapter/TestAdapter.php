<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
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
    protected static $name = 'test';

    const PULL_REQUEST_NUMBER = 40;

    const ISSUE_NUMBER = 7;

    public function isAuthenticated()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenGenerationUrl()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function createFork($org)
    {
        return [
            'remote_url' => 'git@github.com:cordoval/gush.git'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        return ['number' => 77];
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        return [
            'number'       => $id,
            'state'        => "open",
            'user'         => ['login' => 'weaverryan'],
            'assignee'     => ['login' => 'cordoval'],
            'pull_request' => [],
            'milestone'    => ['title' => "Conquer the world"],
            'labels'       => [['name' => 'actionable'], ['name' => 'easy pick']],
            'title'        => 'Write a behat test to launch strategy',
            'body'         => 'Help me conquer the world. Teach them to use gush.',
            'html_url'     => $this->getIssueUrl($id),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        return 'https://github.com/gushphp/gush/issues/' . $id;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        return [
            'number' => self::PULL_REQUEST_NUMBER,
        ];
    }

    /**
     * {@inheritdoc}
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
                'url'   => 'https://api.github.com/repos/gushphp/gush/labels/bug',
                'name'  => 'bug',
                'color' => 'f29513'
            ],
        ];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        return ['html_url' => 'http://github.com/gushphp/gush/pull/' . self::PULL_REQUEST_NUMBER];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getPullRequestUrl($id)
    {
        return 'https://github.com/gushphp/gush/pull/'.$id;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        return [
            'merged'  => true,
            'message' => 'Pull Request successfully merged.',
        ];
    }

    /**]
     * @param string $state
     * @return mixed|void
     */
    public function getPullRequests($state = 'open')
    {
        return [
            [
                'number'     => 17,
                'title'      => 'New feature added',
                'state'      => 'open',
                'created_at' => '2014-04-14 17:24:12',
                'head'       => [
                    'user' => [
                        'login' => 'pierredup'
                    ]
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
    }
}
