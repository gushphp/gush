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

use Gush\Util\ArrayUtil;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitbucketIssueTracker extends BaseIssueTracker
{
    use BitbucketAdapter;

    protected static $validPriorities = [
        'trivial',
        'minor',
        'major',
        'critical',
        'blocker'
    ];

    protected static $validKinds = [
        'bug',
        'enhancement',
        'proposal',
        'task'
    ];

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        $response = $this->client->apiIssues()->create(
            $this->getUsername(),
            $this->getRepository(),
            array_merge(
                $options,
                [
                    'title' => $subject,
                    'content' => $body
                ]
            )
        );

        $resultArray = json_decode($response->getContent(), true);

        return $resultArray['local_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        $response = $this->client->apiIssues()->get(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $resultArray = json_decode($response->getContent(), true);
        $issue = $this->adaptIssueStructure($resultArray);

        return $issue;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        return sprintf('%s/%s/%s/issue/%d', $this->domain, $this->getUsername(), $this->getRepository(), $id);
    }

    protected function detectLabelType($label)
    {
        if (in_array($label, static::$validPriorities)) {
            return 'priority';
        }

        if (in_array($label, static::$validKinds)) {
            return 'kind';
        }

        if (preg_match('/^(v?\d+(\.\d+)*|PR-?\d+)/i', $label)) {
            return 'version';
        }

        return 'component';
    }

    protected function prepareFilter(array $parameters)
    {
        $paramConvert = [
            'created' => ['utc_created_on', 'date'],
            'updated' => ['utc_last_updated', 'date'],
            'state' => ['status', 'string'],
            'creator' => ['reported_by', 'string'],
            'assignee' => ['responsible', 'string'],
            'milestone' => ['milestone', 'string'],
        ];

        $newParams = [];

        if (!empty($parameters['sort'])) {
            if (isset($parameters['direction']) && 'desc' === $parameters['direction']) {
                $newParams['sort'] = '-'.$paramConvert[$parameters['sort']][0];
            } else {
                $newParams['sort'] = $paramConvert[$parameters['sort']][0];
            }
        }

        foreach ($paramConvert as $name => $param) {
            if (!empty($parameters[$name])) {
                $newParams[$paramConvert[$name][0]] = $parameters[$name];
            }
        }

        // Filter only support one condition (no or-case), so its emulated when there is more then one
        if (!empty($parameters['labels']) && false === strpos($parameters['labels'], ',')) {
            $newParams[$this->detectLabelType($parameters['labels'])] = $parameters['labels'];
        }

        return $newParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [], $page = 1, $perPage = 30)
    {
        $this->client->getResultPager()->setPerPage(null);
        $this->client->getResultPager()->setPage(1);

        // since, labels and mentioned are supported, but requires an API change or custom resultPager when limiting

        $response = $this->client->apiIssues()->all(
            $this->getUsername(),
            $this->getRepository(),
            $this->prepareParameters(
                $this->prepareFilter($parameters)
            )
        );

        $resultArray = json_decode($response->getContent(), true);
        $issues = [];

        $priorities = [];
        $kinds = [];
        $versions = [];
        $components = [];

        if (!empty($parameters['labels']) && false !== strpos($parameters['labels'], ',')) {
            $labels = explode(',', $parameters['labels']);
            $labels = array_map('trim', $labels);

            foreach ($labels as $label) {
                switch ($this->detectLabelType($label)) {
                    case 'priority':
                        $priorities[] = $label;
                        break;

                    case 'kind':
                        $kinds[] = $label;
                        break;

                    case 'version':
                        $versions[] = $label;
                        break;

                    default:
                        $components[] = $label;
                        break;
                }
            }
        }

        if (!empty($parameters['since'])) {
            $parameters['since'] = strtotime($parameters['since']);
        }

        foreach ($this->client->getResultPager()->fetch($resultArray, 'issues') as $issue) {
            if (!empty($parameters['since'])) {
                $issueSince = empty($issue['utc_last_updated']) ? $issue['utc_created_on'] : $issue['utc_last_updated'];
                $issueSince = strtotime($issueSince);

                // Only issues updated at or after this time are returned.
                if ($parameters['since'] < $issueSince) {
                    continue;
                }
            }

            // Note this also match in markdown
            // Use a regex to prevent half name matches, @user also matching @username
            if (!empty($parameters['mentioned']) && !preg_match(sprintf('{@%s($|\s)}', preg_quote($parameters['mentioned'])), $issue['content'])) {
                continue;
            }

            if ([] !== $priorities && !in_array($issue['priority'], $priorities, true)) {
                continue;
            }

            if ([] !== $kinds && !in_array($issue['metadata']['kind'], $kinds, true)) {
                continue;
            }

            if ([] !== $versions && !in_array($issue['metadata']['version'], $versions, true)) {
                continue;
            }

            if ([] !== $components && !in_array($issue['metadata']['component'], $components, true)) {
                continue;
            }

            $issues[] = $this->adaptIssueStructure($issue);
        }

        $this->client->getResultPager()->setPage(null);

        return $issues;
    }

    /**
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
        $response = $this->client->apiIssues()->get(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $resultArray = json_decode($response->getContent(), true);

        $newParameters = [
            'responsible' => $resultArray['responsible']['username'],
            'priority' => $resultArray['priority'],
            'kind' => $resultArray['metadata']['kind'],
            'version' => $resultArray['metadata']['version'],
            'component' => $resultArray['metadata']['component'],
        ];

        if (isset($parameters['assignee'])) {
            $newParameters['responsible'] = $parameters['assignee'];
        }

        if (isset($parameters['status'])) {
            $newParameters['status'] = $parameters['status'];
        }

        if (isset($parameters['labels'])) {
            $validVersions = $this->getSupportedVersions();
            $validComponents = $this->getSupportedComponents();

            foreach ($parameters['labels'] as $label) {
                if (in_array($label, static::$validPriorities)) {
                    $newParameters['priority'] = $label;
                } elseif (in_array($label, static::$validKinds)) {
                    $newParameters['kind'] = $label;
                } elseif (in_array($label, $validVersions)) {
                    $newParameters['version'] = $label;
                } elseif (in_array($label, $validComponents)) {
                    $newParameters['component'] = $label;
                } else {
                    throw new \InvalidArgumentException(sprintf('Label "%s" for issues is not supported.', $label));
                }
            }
        }

        $this->client->apiIssues()->update(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $this->prepareParameters($newParameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        $this->client->apiIssues()->update(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            ['status' => 'resolved']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        $response = $this->client->apiIssues()->comments()->create(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $message
        );

        $resultArray = json_decode($response->getContent(), true);

        return ['number' => $resultArray['comment_id']];
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        $response = $this->client->apiIssues()->all(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $resultArray = json_decode($response->getContent(), true);

        $comments = [];

        foreach ($resultArray as $comment) {
            $comments[] = [
                'id' => $comment['comment_id'],
                'url' => sprintf(
                    '%s/%s/%s/issue/%d/#comment-%d',
                    $this->domain,
                    $this->username,
                    $this->getRepository(),
                    $id,
                    $comment['comment_id']
                ),
                'user' => ['login' => $comment['author_info']['username']],
                'body' => $comment['content'],
                'created_at' => new \DateTime($comment['utc_created_on']),
                'updated_at' => !empty($comment['utc_updated_on']) ? new \DateTime($comment['utc_updated_on']) : null,
            ];
        }

        return $comments;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        return array_merge(
            [],
            static::$validKinds,
            static::$validPriorities,
            $this->getSupportedVersions(),
            $this->getSupportedComponents()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        $response = $this->client->apiIssues()->milestones()->all(
            $this->getUsername(),
            $this->getRepository()
        );

        $resultArray = json_decode($response->getContent(), true);

        return ArrayUtil::getValuesFromNestedArray(
            $resultArray,
            'name'
        );
    }

    protected function getSupportedComponents()
    {
        $response = $this->client->apiIssues()->components()->all(
            $this->getUsername(),
            $this->getRepository()
        );

        $resultArray = json_decode($response->getContent(), true);
        $components = [];

        foreach ($resultArray as $comment) {
            $components[] = $comment['name'];
        }

        return $components;
    }

    protected function getSupportedVersions()
    {
        $response = $this->client->apiIssues()->versions()->all(
            $this->getUsername(),
            $this->getRepository()
        );

        $resultArray = json_decode($response->getContent(), true);
        $versions = [];

        foreach ($resultArray as $version) {
            $versions[] = $version['name'];
        }

        return $versions;
    }

    protected function adaptIssueStructure(array $issue)
    {
        $labels = [
            $issue['metadata']['kind'],
            $issue['priority'],
        ];

        if (!empty($issue['metadata']['component'])) {
            $labels[] = $issue['metadata']['component'];
        }

        if (!empty($issue['metadata']['version'])) {
            $labels[] = $issue['metadata']['version'];
        }

        return [
            'url'          => $this->getIssueUrl($issue['local_id']),
            'number'       => $issue['local_id'],
            'state'        => $issue['status'],
            'title'        => $issue['title'],
            'body'         => $issue['content'],
            'user'         => $issue['reported_by']['username'],
            'labels'       => $labels,
            'assignee'     => (isset($issue['responsible']) ? $issue['responsible']['username'] : ''),
            'milestone'    => (isset($issue['metadata']['milestone']) && !is_null($issue['metadata']['milestone'])) ? $issue['metadata']['milestone'] : null,
            'created_at'   => new \DateTime($issue['utc_created_on']),
            'updated_at'   => !empty($issue['utc_last_updated']) ? new \DateTime($issue['utc_last_updated']) : null,
            'closed_by'    => null,
            'pull_request' => false,
        ];
    }
}
