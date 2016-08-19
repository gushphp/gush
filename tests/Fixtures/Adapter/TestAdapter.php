<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Fixtures\Adapter;

use Gush\Adapter\BaseAdapter;
use Gush\Adapter\IssueTracker;

class TestAdapter extends BaseAdapter implements IssueTracker
{
    const PULL_REQUEST_NUMBER = 40;
    const ISSUE_NUMBER = 7;
    const ISSUE_NUMBER_CREATED = 77;
    const RELEASE_ASSET_NUMBER = 1;

    private $name;
    private $pullRequest = [];
    private $issue = [];

    const MERGE_HASH = '32fe234332fe234332fe234332fe234332fe2343';

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getAdapterName()
    {
        return $this->name;
    }

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
    public function getRepositoryInfo($org, $repository)
    {
        if ('cordoval' === $org || 'user' === $org) {
            return [
                'owner' => 'cordoval',
                'html_url' => 'https://github.com/cordoval/gush',
                'fetch_url' => 'https://github.com/cordoval/gush.git',
                'push_url' => 'git@github.com:cordoval/gush.git',
                'is_fork' => true,
                'is_private' => false,
                'fork_origin' => [
                    'org' => 'gushphp',
                    'repository' => 'gush',
                ],
            ];
        } else {
            return [
                'owner' => 'cordoval',
                'html_url' => 'https://github.com/gushphp/gush',
                'fetch_url' => 'https://github.com/gushphp/gush.git',
                'push_url' => 'git@github.com:gushphp/gush.git',
                'is_fork' => false,
                'is_private' => false,
                'fork_origin' => null,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        $this->issue = [
            'url' => $this->getIssueUrl(self::ISSUE_NUMBER_CREATED),
            'number' => self::ISSUE_NUMBER_CREATED,
            'state' => 'open',
            'title' => $subject,
            'body' => $body,
            'user' => 'weaverryan',
            'labels' => isset($options['labels']) ? $options['labels'] : [],
            'assignee' => isset($options['assignee']) ? $options['assignee'] : null,
            'milestone' => isset($options['milestone']) ? $options['milestone'] : null,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
            'closed_by' => null,
            'pull_request' => false,

            // debugging info
            'org' => $this->username,
            'repo' => $this->repository,
        ];

        return 77;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        if (self::ISSUE_NUMBER_CREATED === $id) {
            if (!$this->issue) {
                throw new \RuntimeException(
                    sprintf(
                        'ID #%d is reserved for testing, call openIssue() first before using this id',
                        self::ISSUE_NUMBER_CREATED
                    )
                );
            }

            return $this->issue;
        }

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

            // debugging info
            'org' => $this->username,
            'repo' => $this->repository,
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
    public function getIssues(array $parameters = [], $limit = 30)
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
        $url = 'https://github.com/gushphp/gush/issues/'.$id.'#issuecomment-2';
        $this->pullRequest['comments'][2] = [
            'id' => 2,
            'url' => $url,
            'body' => $message,
            'user' => 'phansys',
            'created_at' => new \DateTime('1969-12-31T10:00:00+0100'),
            'updated_at' => new \DateTime('1969-12-31T10:00:00+0100'),
        ];

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        $prComments = [];
        if (isset($this->pullRequest['comments'])) {
            $prComments = $this->pullRequest['comments'];
        }

        $prComments += [
            1 => [
                'id' => 1,
                'url' => 'https://github.com/gushphp/gush/issues/'.$id.'#issuecomment-2',
                'body' => 'Seems good to me',
                'user' => 'sstok',
                'created_at' => new \DateTime('1969-12-31T10:00:00+0100'),
                'updated_at' => new \DateTime('1969-12-31T10:00:00+0100'),
            ],
        ];

        if ($id && isset($prComments[$id])) {
            return $prComments[$id];
        }

        return $prComments;
    }

    /**
     * @return mixed
     */
    public function getLabels()
    {
        return ['bug', 'feature', 'documentation'];
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
        list($sourceOrg, $sourceBranch) = explode(':', $head);

        $this->pullRequest = [
            'url' => 'https://github.com/gushphp/gush/pull/'.self::PULL_REQUEST_NUMBER,
            'number' => self::PULL_REQUEST_NUMBER,
            'state' => 'open',
            'title' => $subject,
            'body' => $body,
            'labels' => [],
            'milestone' => 'some_good_stuff',
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
            'user' => 'cordoval',
            'assignee' => null,
            'merged' => false,
            'merged_by' => null,
            'head' => [
                'ref' => $sourceBranch,
                'sha' => '6dcb09b5b57875f334f61aebed695e2e4193db5e',
                'user' => $sourceOrg,
                'repo' => 'gush',
            ],
            'base' => [
                'ref' => $base,
                'label' => 'base_ref',
                'sha' => '6dcb09b5b57875f334f61acmes695e2e4193db5e',
                'user' => 'gushphp',
                'repo' => 'gush',
            ],
        ];

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
        if (self::PULL_REQUEST_NUMBER === $id) {
            if (!$this->pullRequest) {
                throw new \RuntimeException(
                    sprintf(
                        'ID #%d is reserved for testing, call openPullRequest() first before using this id',
                        self::PULL_REQUEST_NUMBER
                    )
                );
            }

            return $this->pullRequest;
        }

        return [
            'url' => 'https://github.com/gushphp/gush/pull/'.$id,
            'number' => $id,
            'state' => 'open',
            'title' => 'Write a behat test to launch strategy',
            'body' => 'Help me conquer the world. Teach them to use Gush.',
            'labels' => ['actionable', 'easy pick'],
            'milestone' => 'some_good_stuff',
            'created_at' => new \DateTime('1969-12-31T10:00:00+0100'),
            'updated_at' => new \DateTime('1969-12-31T10:00:00+0100'),
            'user' => 'weaverryan',
            'assignee' => 'cordoval',
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
                'user' => 'gushphp',
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
                'sha' => self::MERGE_HASH,
                'message' => 'added merge pull request feature',
                'user' => 'cordoval',
            ],
            [
                'sha' => 'ab34567812345678123456781234567812345678',
                'message' => 'added final touches',
                'user' => 'cordoval',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        return self::MERGE_HASH;
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
    public function switchPullRequestBase($prNumber, $newBase, $newHead, $forceNewPr = false)
    {
        $pr = $this->getPullRequest($prNumber);

        if ($forceNewPr) {
            $newPr = $this->openPullRequest(
                $newBase,
                $newHead,
                $pr['title'],
                $pr['body']
            );

            $this->closePullRequest($prNumber);

            return $newPr;
        }

        return ['html_url' => $this->getPullRequestUrl($prNumber), 'number' => $prNumber];
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequests($state = null, $limit = 30)
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
            ],
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
                'url' => 'https://github.com/octocat/Hello-World/releases/v1.0.0',
                'id' => 1,
                'name' => 'v1.0.0',
                'tag_name' => 'v1.0.0',
                'body' => 'Description of the release',
                'draft' => false,
                'prerelease' => false,
                'created_at' => new \DateTime('2014-01-05T10:00:12+0100'),
                'published_at' => new \DateTime('2014-01-05T10:00:12+0100'),
                'user' => 'username',
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
            ],
        ];
    }
}
