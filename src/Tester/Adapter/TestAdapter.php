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
use Gush\Adapter\IssueTracker;

class TestAdapter extends BaseAdapter implements IssueTracker
{
    const PULL_REQUEST_NUMBER = 40;

    const ISSUE_NUMBER = 7;

    const RELEASE_ASSET_NUMBER = 1;

    /**
     * {@inheritdoc}
     */
    public function supportsRepository($remoteUrl)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
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
            'git_url' => 'git@github.com:cordoval/gush.git',
            'html_url' => 'https://github.com/cordoval/gush',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        return 77;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        return [
            'url' => $this->getIssueUrl($id),
            'number' => $id,
            'state' => 'open',
            'title' => 'Write a behat test to launch strategy',
            'body' => 'Help me conquer the world. Teach them to use Gush.',
            'user' => 'weaverryan',
            'labels' => ['actionable', 'easy pick'],
            'assignee' => 'cordoval',
            'milestone' => 'v1.0',
            'created_at' => new \DateTime('2014-05-14T15:30:00+0100'),
            'updated_at' => new \DateTime('2014-05-14T15:30:00+0100'),
            'closed_by' => null,
            'pull_request' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        return 'https://github.com/gushphp/gush/issues/'.$id;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [], $page = 1, $perPage = 30)
    {
        return [
            [
                'url' => $this->getIssueUrl(1),
                'number' => 1,
                'state' => 'open',
                'title' => 'easy issue',
                'body' => 'this issue is easy',
                'user' => 'cordoval',
                'labels' => ['critic', 'easy pick'],
                'assignee' => 'cordoval',
                'milestone' => 'good_release',
                'created_at' => new \DateTime('1969-12-31T10:00:00+0100'),
                'updated_at' => new \DateTime('1969-12-31T10:00:00+0100'),
                'closed_by' => null,
                'pull_request' => true,
            ],
            [
                'url' => $this->getIssueUrl(2),
                'number' => 2,
                'state' => 'open',
                'title' => 'hard issue',
                'body' => 'this issue is not so easy',
                'user' => 'weaverryan',
                'labels' => ['critic'],
                'assignee' => 'cordoval',
                'milestone' => 'some_good_stuff',
                'created_at' => new \DateTime('1969-12-31T10:00:00+0100'),
                'updated_at' => new \DateTime('1969-12-31T12:00:00+0100'),
                'closed_by' => null,
                'pull_request' => false,
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
    public function updatePullRequest($id, array $parameters)
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
        return 'https://github.com/gushphp/gush/issues/'.$id.'#issuecomment-1';
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        return [
            [
                "id" => 1,
                "url" => 'https://github.com/gushphp/gush/issues/'.$id.'#issuecomment-2',
                "body" => "Seems good to me",
                "user" => "sstok",
                "created_at" => new \DateTime('1969-12-31T10:00:00+0100'),
                "updated_at" => new \DateTime('1969-12-31T10:00:00+0100'),
            ]
        ];
    }

    /**
     * @return mixed
     */
    public function getLabels()
    {
        return ['bug'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        return ['version 1.0'];
    }

    /**
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        return [
            'html_url' => 'https://github.com/gushphp/gush/pull/'.self::PULL_REQUEST_NUMBER,
            'number' => self::PULL_REQUEST_NUMBER,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequest($id)
    {
        return [
            'url' => 'https://github.com/gushphp/gush/pull/'.$id,
            'number' => $id,
            'state' => 'open',
            'title' => 'Write a behat test to launch strategy',
            'body' => 'Help me conquer the world. Teach them to use gush.',
            'labels' => ['actionable', 'easy pick'],
            'milestone' => 'some_good_stuff',
            'created_at' => new \DateTime('1969-12-31T10:00:00+0100'),
            'updated_at' => new \DateTime('1969-12-31T10:00:00+0100'),
            'user' => 'weaverryan',
            'assignee' => 'cordoval',
            'merge_commit' => null, // empty as the pull request is not merged
            'merged' => false,
            'merged_by' => null,
            'head' => [
                'ref' => 'head_ref',
                'sha' => '6dcb09b5b57875f334f61aebed695e2e4193db5e',
                'user' => 'cordoval',
                'repo' => 'gush',
            ],
            'base' => [
              'ref' => 'base_ref',
              'label' => 'base_ref',
              'sha' => '6dcb09b5b57875f334f61acmes695e2e4193db5e',
              'repo' => 'gush',
            ],
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
                'sha' => '32fe234332fe234332fe234332fe234332fe2343',
                'message' => 'added merge pull request feature',
                'user' => 'cordoval'
            ],
            [
                'sha' => 'ab34567812345678123456781234567812345678',
                'message' => 'added final touches',
                'user' => 'cordoval'
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        return '32fe234332fe234332fe234332fe234332fe2343';
    }

    /**
     * {@inheritdoc}
     */
    public function closePullRequest($id)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequests($state = null, $page = 1, $perPage = 30)
    {
        return [
            [
                'url' => 'https://github.com/gushphp/gush/pull/17',
                'number' => 17,
                'state' => 'open',
                'title' => 'New feature added',
                'body' => 'Help me conquer the world. Teach them to use gush.',
                'labels' => ['actionable', 'easy pick'],
                'milestone' => 'some_good_stuff',
                'created_at' => new \DateTime('2014-04-14T17:24:12+0100'),
                'updated_at' => new \DateTime('2014-04-14T17:24:12+0100'),
                'user' => 'pierredup',
                'assignee' => 'cordoval',
                'merge_commit' => null, // empty as the pull request is not merged
                'merged' => false,
                'merged_by' => null,
                'head' => [
                    'ref' => 'head_ref',
                    'sha' => '6dcb09b5b57875f334f61aebed695e2e4193db5e',
                    'user' => 'pierredup',
                    'repo' => 'gush',
                ],
                'base' => [
                    'ref' => 'base_ref',
                    'sha' => '6dcb09b5b57875f334f61acmes695e2e4193db5e',
                    'repo' => 'gush',
                ],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestStates()
    {
        return [
            'open',
            'closed',
            'all',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createRelease($name, array $parameters = [])
    {
        return 'https://github.com/gushphp/gush/releases/'.$name;
    }

    /**
     * @return mixed
     */
    public function getReleases()
    {
        return [
            [
                "url" => "https://github.com/octocat/Hello-World/releases/v1.0.0",
                "id" => 1,
                "name" => "v1.0.0",
                "tag_name" => "v1.0.0",
                "body" => "Description of the release",
                "draft" => false,
                "prerelease" => false,
                "created_at" => new \DateTime('2014-01-05T10:00:12+0100'),
                "published_at" => new \DateTime('2014-01-05T10:00:12+0100'),
                "user" => "username",
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
        return self::RELEASE_ASSET_NUMBER;
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseAssets($id)
    {
        return [
            [
                'url' => 'https://api.github.com/repos/octocat/Hello-World/releases/assets/'.$id,
                'id' => 1,
                'name' => 'example.zip',
                'label' => 'short description',
                'state' => 'uploaded',
                'content_type' => 'application/zip',
                'size' => 1024,
                'created_at' => new \DateTime('2014-01-05T10:00:12+0100'),
                'updated_at' => new \DateTime('2014-01-05T10:00:12+0100'),
                'uploader' => 'username',
            ]
        ];
    }
}
