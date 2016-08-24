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

use Gush\Adapter\BaseAdapter;
use Gush\Exception\UnsupportedOperationException;
use Gush\ThirdParty\Gitlab\Model\Issue;
use Gush\ThirdParty\Gitlab\Model\MergeRequest;
use Gush\ThirdParty\Gitlab\Model\Project;
use Gush\Util\ArrayUtil;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Julien Bianchi <contact@jubianchi.fr>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GitLabRepoAdapter extends BaseAdapter
{
    use GitLabAdapter;

    public function supportsRepository($remoteUrl)
    {
        return false !== stripos($remoteUrl, parse_url($this->configuration['repo_domain_url'])['host']);
    }

    /**
     * {@inheritdoc}
     */
    public function createFork($org)
    {
        throw new UnsupportedOperationException('Forking is not supported by Gitlab');
    }

    public function getRepositoryInfo($org, $repository)
    {
        return Project::castFrom(
            $this->findProject($org, $repository)
        )->toArray();
    }

    public function getPullRequestUrl($id)
    {
        return sprintf(
            '%s/merge_requests/%d',
            $this->configuration['repo_domain_url'],
            $this->getPullRequest($id)['iid']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        $issue = MergeRequest::fromArray(
            $this->client,
            $this->getCurrentProject(),
            $this->client->api('merge_requests')->show($this->getCurrentProject()->id, $id)
        );

        $comment = $issue->addComment($message);

        return sprintf('%s#note_%d', $this->getPullRequestUrl($id), $comment->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        $comments = $this->client->api('merge_requests')->showNotes($this->getCurrentProject()->id, $id);

        return array_filter(array_map(function ($note) {
            return [
                'id' => $note['id'],
                'user' => $note['author']['username'],
                'body' => $note['body'],
                'created_at' => !empty($note['created_at']) ? new \DateTime($note['created_at']) : null,
                'updated_at' => !empty($note['updated_at']) ? new \DateTime($note['updated_at']) : null,
            ];
        }, $comments));
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        return ArrayUtil::getValuesFromNestedArray(
            $this->client->api('projects')->labels($this->getCurrentProject()->id),
            'name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        return $this->client->api('milestones')->all($this->getCurrentProject()->id);
    }

    /**
     * {@inheritdoc}
     */
    public function updatePullRequest($id, array $parameters)
    {
        $issue = $this->client->api('merge_requests')->show($this->getCurrentProject()->id, $id);
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
    public function closePullRequest($id)
    {
        $mr = MergeRequest::fromArray(
            $this->client,
            $this->getCurrentProject(),
            $this->client->api('merge_requests')->show($this->getCurrentProject()->id, $id)
        );

        return $mr->close()->id;
    }

    /**
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        $head = explode(':', $head);
        $mr = $this->getCurrentProject()->createMergeRequest(
            $head[1],
            $base,
            $subject,
            null,
            $body
        );

        return [
            'html_url' => $this->getPullRequestUrl($mr->id),
            'number' => $mr->iid,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequest($id)
    {
        $mr = MergeRequest::fromArray(
            $this->client,
            $this->getCurrentProject(),
            $this->client->api('merge_requests')->show($this->getCurrentProject()->id, $id)
        );

        $data = array_merge(
            $mr->toArray(),
            [
                'url' => sprintf(
                    '%s/%s/%s/merge_requests/%d',
                    $this->configuration['repo_domain_url'],
                    $this->getUsername(),
                    $this->getRepository(),
                    $mr->iid
                ),
            ]
        );
        $data['milestone'] = $data['milestone']->title;
        $data['user'] = $data['author'];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestCommits($id)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequests($state = null, $limit = 30)
    {
        $mergeRequests = $this->client->api('merge_requests')->all($this->getCurrentProject()->id);

        if (null !== $state) {
            $mergeRequests = array_filter(
                $mergeRequests,
                function ($mr) use ($state) {
                    return $mr['state'] === $state;
                }
            );
        }

        return array_map(
            function ($mr) {
                return MergeRequest::fromArray($this->client, $this->getCurrentProject(), $mr)->toArray();
            },
            $mergeRequests
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestStates()
    {
        return ['opened', 'closed', 'merged'];
    }

    /**
     * {@inheritdoc}
     */
    public function createRelease($name, array $parameters = [])
    {
        throw new UnsupportedOperationException('Releases are not supported by Gitlab.');
    }

    /**
     * {@inheritdoc}
     */
    public function getReleases()
    {
        throw new UnsupportedOperationException('Releases are not supported by Gitlab.');
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
        throw new UnsupportedOperationException('Releases are not supported by Gitlab.');
    }

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
        throw new UnsupportedOperationException('Releases are not supported by Gitlab.');
    }
}
