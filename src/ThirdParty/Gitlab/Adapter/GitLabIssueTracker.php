<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Gitlab\Adapter;

use Gush\Adapter\BaseIssueTracker;
use Gush\Exception\UnsupportedOperationException;
use Gush\ThirdParty\Gitlab\Model\Issue;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
class GitLabIssueTracker extends BaseIssueTracker
{
    use GitLabAdapter;

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        $issue = $this->getCurrentProject()->createIssue(
            $subject,
            [
                'description' => $body,
            ]
        );

        return $issue->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        return Issue::fromArray(
            $this->client,
            $this->getCurrentProject(),
            $this->client->api('issues')->show($this->getCurrentProject()->id, $id)
        )->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        return sprintf(
            '%s/%s/%s/issues/%d',
            $this->configuration['repo_domain_url'],
            $this->getUsername(),
            $this->getRepository(),
            $this->getIssue($id)['iid']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [], $page = 1, $perPage = 30)
    {
        $issues = $this->client->api('issues')->all($this->getCurrentProject()->id);

        if (isset($parameters['state'])) {
            $parameters['state'] = $parameters['state'] === 'open' ? 'opened' : 'closed';

            $issues = array_filter(
                $issues,
                function ($issue) use ($parameters) {
                    return $issue['state'] === $parameters['state'];
                }
            );
        }

        if (isset($parameters['creator'])) {
            $issues = array_filter(
                $issues,
                function ($issue) use ($parameters) {
                    return $issue['user']['login'] === $parameters['creator'];
                }
            );
        }

        if (isset($parameters['assignee'])) {
            $issues = array_filter(
                $issues,
                function ($issue) use ($parameters) {
                    return $issue['assignee']['login'] === $parameters['assignee'];
                }
            );
        }

        return array_map(
            function ($issue) {
                if (isset($issue['milestone']['title'])) {
                    $issue['milestone'] = $issue['milestone']['title'];
                }

                return Issue::fromArray($this->client, $this->getCurrentProject(), $issue)->toArray();
            },
            $issues
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
        $issue = $this->client->api('issues')->show($this->getCurrentProject()->id, $id);
        $issue = Issue::fromArray($this->client, $this->getCurrentProject(), $issue);

        if (isset($parameters['assignee'])) {
            $assignee = $this->client->api('users')->search($parameters['assignee']);

            if (count($assignee) === 0) {
                throw new \InvalidArgumentException(sprintf('Could not find user %s', $parameters['assignee']));
            }

            $issue->update([
                'assignee_id' => current($assignee)['id'],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        $issue = $this->client->api('issues')->show($this->getCurrentProject()->id, $id);

        Issue::fromArray($this->client, $this->getCurrentProject(), $issue);
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        $issue = Issue::fromArray(
            $this->client,
            $this->getCurrentProject(),
            $this->client->api('issues')->show($this->getCurrentProject()->id, $id)
        );

        $comment = $issue->addComment($message);

        return sprintf('%s#note_%d', $this->getIssueUrl($id), $comment->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        $issue = Issue::fromArray(
            $this->client,
            $this->getCurrentProject(),
            $this->client->api('issues')->show($this->getCurrentProject()->id, $id)
        );

        return $issue->showComments();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        throw new UnsupportedOperationException('Labels are not supported by Gitlab');
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        return array_map(
            function ($milestone) {
                return $milestone['title'];
            },
            $this->client->api('milestones')->all($this->getCurrentProject()->id)
        );
    }
}
