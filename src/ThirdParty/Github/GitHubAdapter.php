<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Github;

use Github\Client;
use Github\Exception\ValidationFailedException;
use Github\HttpClient\CachedHttpClient;
use Github\ResultPager;
use Gush\Adapter\BaseAdapter;
use Gush\Adapter\IssueTracker;
use Gush\Adapter\SupportsDynamicLabels;
use Gush\Config;
use Gush\Exception\AdapterException;
use Gush\Util\ArrayUtil;
use Guzzle\Plugin\Log\LogPlugin;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GitHubAdapter extends BaseAdapter implements IssueTracker, SupportsDynamicLabels
{
    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $domain;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $authenticationType = Client::AUTH_HTTP_PASSWORD;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Gush\Config
     */
    protected $globalConfig;

    /**
     * @param array  $config
     * @param Config $globalConfig
     */
    public function __construct(array $config, Config $globalConfig)
    {
        $this->config = $config;
        $this->globalConfig = $globalConfig;
        $this->client = $this->buildGitHubClient();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRepository($remoteUrl)
    {
        return false !== stripos($remoteUrl, 'github.com');
    }

    /**
     * @return Client
     */
    protected function buildGitHubClient()
    {
        $cachedClient = new CachedHttpClient(
            [
                'cache_dir' => $this->globalConfig->get('cache-dir'),
                'base_url' => $this->config['base_url'],
            ]
        );

        $client = new Client($cachedClient);

        if (false !== getenv('GITHUB_DEBUG')) {
            $logPlugin = LogPlugin::getDebugPlugin();
            $httpClient = $client->getHttpClient();
            $httpClient->addSubscriber($logPlugin);
        }

        $client->setOption('base_url', $this->config['base_url']);
        $this->url = rtrim($this->config['base_url'], '/');
        $this->domain = rtrim($this->config['repo_domain_url'], '/');

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $credentials = $this->config['authentication'];

        if (Client::AUTH_HTTP_PASSWORD === $credentials['http-auth-type']) {
            $this->client->authenticate(
                $credentials['username'],
                $credentials['password-or-token'],
                $credentials['http-auth-type']
            );
        } else {
            $this->client->authenticate(
                $credentials['password-or-token'],
                $credentials['http-auth-type']
            );
        }

        $this->authenticationType = $credentials['http-auth-type'];
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        if (Client::AUTH_HTTP_PASSWORD === $this->authenticationType) {
            return is_array(
                $this->client->api('authorizations')->all()
            );
        }

        return is_array($this->client->api('me')->show());
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenGenerationUrl()
    {
        return sprintf('%s/settings/applications', $this->url);
    }

    /**
     * {@inheritdoc}
     */
    public function createFork($org)
    {
        $api = $this->client->api('repo');

        $result = $api->forks()->create(
            $this->getUsername(),
            $this->getRepository(),
            $org && $this->config['authentication']['username'] !== $org ? ['organization' => $org] : []
        );

        return [
            'git_url' => $result['ssh_url'],
            'html_url' => $result['html_url'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createRepo(
        $name,
        $description,
        $homepage,
        $public = true,
        $organization = null,
        $hasIssues = true,
        $hasWiki = false,
        $hasDownloads = false,
        $teamId = 0,
        $autoInit = true
    ) {
        $repo = $this->client->api('repo');
        $result = $repo->create(
            $name,
            $description,
            $homepage,
            $public,
            $organization,
            $hasIssues,
            $hasWiki,
            $hasDownloads,
            $teamId,
            $autoInit
        );

        return [
            'git_url' => $result['ssh_url'],
            'html_url' => $result['html_url'],
        ];
    }

    public function getRepositoryInfo($org, $repository)
    {
        $repo = $this->client->api('repo')->show($org, $repository);
        $info = [
            'owner' => $org,
            'html_url' => $repo['html_url'],
            'fetch_url' => $repo['private'] ? $repo['git_url'] : $repo['clone_url'],
            'push_url' => $repo['ssh_url'],
            'is_fork' => $repo['fork'],
            'is_private' => $repo['private'],
            'fork_origin' => null,
        ];

        if ($repo['fork']) {
            $info['fork_origin'] = [
              'org' => $repo['source']['owner']['login'],
              'repo' => $repo['name'],
            ];
        }

        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        $api = $this->client->api('issue');

        $issue = $api->create(
            $this->getUsername(),
            $this->getRepository(),
            array_merge($options, ['title' => $subject, 'body' => $body])
        );

        return $issue['number'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        $api = $this->client->api('issue');

        return $this->adaptIssueStructure(
            $api->show(
                $this->getUsername(),
                $this->getRepository(),
                $id
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        return sprintf('%s/%s/%s/issues/%d', $this->domain, $this->getUsername(), $this->getRepository(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [], $page = 1, $perPage = 30)
    {
        // FIXME is not respecting the pagination

        $pager = new ResultPager($this->client);
        $fetchedIssues = $pager->fetchAll(
            $this->client->api('issue'),
            'all',
            [
                $this->getUsername(),
                $this->getRepository(),
                $parameters,
            ]
        );

        $issues = [];

        foreach ($fetchedIssues as $issue) {
            $issues[] = $this->adaptIssueStructure($issue);
        }

        return $issues;
    }

    /**
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
        $api = $this->client->api('issue');

        $api->update(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $parameters
        );
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        $this->updateIssue($id, ['state' => 'closed']);
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        $api = $this->client->api('issue')->comments();

        $comment = $api->create(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            ['body' => $message]
        );

        return $comment['html_url'];
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        $pager = new ResultPager($this->client);

        $fetchedComments = $pager->fetchAll(
            $this->client->api('issue')->comments(),
            'all',
            [
                $this->getUsername(),
                $this->getRepository(),
                $id,
            ]
        );

        $comments = [];

        foreach ($fetchedComments as $comment) {
            $comments[] = [
                'id' => $comment['id'],
                'url' => $comment['html_url'],
                'body' => $comment['body'],
                'user' => $comment['user']['login'],
                'created_at' => !empty($comment['created_at']) ? new \DateTime($comment['created_at']) : null,
                'updated_at' => !empty($comment['updated_at']) ? new \DateTime($comment['updated_at']) : null,
            ];
        }

        return $comments;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        $api = $this->client->api('issue')->labels();

        return ArrayUtil::getValuesFromNestedArray(
            $api->all(
                $this->getUsername(),
                $this->getRepository()
            ),
            'name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        $api = $this->client->api('issue')->milestones();

        return ArrayUtil::getValuesFromNestedArray(
            $api->all(
                $this->getUsername(),
                $this->getRepository(),
                $parameters
            ),
            'title'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        $api = $this->client->api('pull_request');

        try {
            $result = $api->create(
                $this->getUsername(),
                $this->getRepository(),
                array_merge(
                    $parameters,
                    [
                        'base' => $base,
                        'head' => $head,
                        'title' => $subject,
                        'body' => $body,
                    ]
                )
            );
        } catch (ValidationFailedException $exception) {
            if (isset($parameters['issue']) &&
                'Validation Failed: Field "issue" is invalid, for resource "PullRequest"' === $exception->getMessage()
            ) {
                throw new \Exception(
                    'Pull Request already opened for given issue '.
                    $this->getPullRequestUrl($parameters['issue'])
                );
            }

            throw $exception;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequest($id)
    {
        $api = $this->client->api('pull_request');

        return $this->adaptPullRequestStructure(
            $api->show(
                $this->getUsername(),
                $this->getRepository(),
                $id
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestUrl($id)
    {
        return sprintf('%s/%s/%s/pull/%d', $this->domain, $this->getUsername(), $this->getRepository(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestCommits($id)
    {
        $api = $this->client->api('pull_request');

        $fetchedCommits = $api->commits(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $commits = [];

        foreach ($fetchedCommits as $commit) {
            $commits[] = [
                'sha' => $commit['sha'],
                'user' => $commit['author']['login'],
                'message' => $commit['commit']['message'],
            ];
        }

        return $commits;
    }

    /**
     * Gets the status of a commit reference.
     *
     * @param string $org
     * @param string $repo
     * @param string $hash
     *
     * @return array[]
     */
    public function getCommitStatuses($org, $repo, $hash)
    {
        $pager = new ResultPager($this->client);

        return $pager->fetchAll($this->client->api('repo')->statuses(), 'combined', [$org, $repo, $hash])['statuses'];
    }

    /**
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        $api = $this->client->api('pull_request');

        $result = $api->merge(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $message
        );

        if (false === $result['merged']) {
            throw new AdapterException('Merge failed: '.$result['message']);
        }

        return $result['sha'];
    }

    /**
     * {@inheritdoc}
     */
    public function updatePullRequest($id, array $parameters)
    {
        $api = $this->client->api('pull_request');

        $api->update(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $parameters
        );
    }

    /**
     * {@inheritdoc}
     */
    public function closePullRequest($id)
    {
        $this->updatePullRequest($id, ['state' => 'closed']);
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequests($state = null, $page = 1, $perPage = 30)
    {
        // FIXME is not respecting the pagination

        $api = $this->client->api('pull_request');
        $fetchedPrs = $api->all(
            $this->getUsername(),
            $this->getRepository(),
            ['state' => $state]
        );

        $prs = [];

        foreach ($fetchedPrs as $pr) {
            $prs[] = $this->adaptPullRequestStructure($pr);
        }

        return $prs;
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
        $api = $this->client->api('repo')->releases();

        $release = $api->create(
            $this->getUsername(),
            $this->getRepository(),
            array_merge(
                $parameters,
                [
                    'tag_name' => $name,
                ]
            )
        );

        return [
            'url' => $release['html_url'],
            'id' => $release['id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getReleases()
    {
        $api = $this->client->api('repo')->releases();

        $fetchedReleases = $api->all(
            $this->getUsername(),
            $this->getRepository()
        );

        $releases = [];

        foreach ($fetchedReleases as $release) {
            $releases[] = [
                'url' => $release['html_url'],
                'id' => $release['id'],
                'name' => $release['name'],
                'tag_name' => $release['tag_name'],
                'body' => $release['body'],
                'draft' => $release['draft'],
                'prerelease' => $release['prerelease'],
                'created_at' => new \DateTime($release['created_at']),
                'updated_at' => !empty($release['updated_at']) ? new \DateTime($release['updated_at']) : null,
                'published_at' => !empty($release['published_at']) ? new \DateTime($release['published_at']) : null,
                'user' => $release['author']['login'],
            ];
        }

        return $releases;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
        $api = $this->client->api('repo')->releases();

        $api->remove(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
        $api = $this->client->api('repo')->releases()->assets();

        $asset = $api->create(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $name,
            $contentType,
            $content
        );

        return $asset['id'];
    }

    protected function adaptIssueStructure(array $issue)
    {
        return [
            'url' => $issue['html_url'],
            'number' => $issue['number'],
            'state' => $issue['state'],
            'title' => $issue['title'],
            'body' => $issue['body'],
            'user' => $issue['user']['login'],
            'labels' => ArrayUtil::getValuesFromNestedArray($issue['labels'], 'name'),
            'assignee' => $issue['assignee']['login'],
            'milestone' => $issue['milestone']['title'],
            'created_at' => new \DateTime($issue['created_at']),
            'updated_at' => !empty($issue['updated_at']) ? new \DateTime($issue['updated_at']) : null,
            'closed_by' => !empty($issue['closed_by']) ? $issue['closed_by']['login'] : null,
            'pull_request' => isset($issue['pull_request']),
        ];
    }

    protected function adaptPullRequestStructure(array $pr)
    {
        return [
            'url' => $pr['html_url'],
            'number' => $pr['number'],
            'state' => $pr['state'],
            'title' => $pr['title'],
            'body' => $pr['body'],
            'labels' => [],
            'milestone' => null,
            'created_at' => new \DateTime($pr['created_at']),
            'updated_at' => !empty($pr['updated_at']) ? new \DateTime($pr['updated_at']) : null,
            'user' => $pr['user']['login'],
            'assignee' => null,
            'merge_commit' => null, // empty as GitHub doesn't provide this yet, merge_commit_sha is deprecated and not meant for this
            'merged' => isset($pr['merged_by']) && isset($pr['merged_by']['login']),
            'merged_by' => isset($pr['merged_by']) && isset($pr['merged_by']['login']) ? $pr['merged_by']['login'] : '',
            'head' => [
                'ref' => $pr['head']['ref'],
                'sha' => $pr['head']['sha'],
                'user' => $pr['head']['repo']['owner']['login'],
                'repo' => $pr['head']['repo']['name'],
            ],
            'base' => [
                'ref' => $pr['base']['ref'],
                'label' => $pr['base']['label'],
                'sha' => $pr['base']['sha'],
                'repo' => $pr['base']['repo']['name'],
                'user' => $pr['base']['repo']['owner']['login'],
            ],
        ];
    }
}
