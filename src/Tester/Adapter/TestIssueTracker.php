<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tester\Adapter;

use Gush\Adapter\IssueTracker;

class TestIssueTracker implements IssueTracker
{
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
            'user' => 'sstok',
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
            ],
            [
                'url' => $this->getIssueUrl(2),
                'number' => 2,
                'state' => 'open',
                'title' => 'hard issue',
                'body' => 'this issue is not so easy',
                'user' => 'sstok',
                'labels' => ['critic'],
                'assignee' => 'cordoval',
                'milestone' => 'some_good_stuff',
                'created_at' => new \DateTime('1969-12-31T10:00:00+0100'),
                'updated_at' => new \DateTime('1969-12-31T12:00:00+0100'),
                'closed_by' => null,
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
}
