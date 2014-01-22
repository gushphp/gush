<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Github\ResultPager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Gush\Feature\TableFeature;
use Gush\Feature\GitHubFeature;

/**
 * Lists the issues
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class IssueListCommand extends BaseCommand implements TableFeature, GitHubFeature
{
    protected $enum = [
        'filter' => [
            'assigned',
            'created',
            'mentioned',
            'subscribed',
            'all',
        ],
        'state' => ['open', 'closed'],
        'sort' => ['created', 'updated'],
        'direction' => ['asc', 'desc'],
        'type' => ['pr', 'issue'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:list')
            ->setDescription('List issues')
            ->addOption('label', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Specify a label')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('filter'))
            ->addOption('state', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('state'))
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('sort'))
            ->addOption('direction', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('direction'))
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only issues after this time are displayed.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('type'))
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists issues from either the current or the given organization
and repository:

    <info>$ php %command.full_name%</info>
    <info>$ php %command.full_name% --filter=created --sort=created --direction=desc --since="6 months ago"
    --type=pr</info>

All of the parameters provided by the github API are supported:

    http://developer.github.com/v3/issues/#list-issues

With the addition of the <info>--type</info> option which enables you to filter show only pull-requests or only issues.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');

        $client = $this->getGithubClient();
        $paginator = new ResultPager($client);

        $params = [];

        foreach (['state', 'filter', 'sort', 'direction'] as $key) {
            if ($v = $input->getOption($key)) {
                $this->validateEnum($key, $v);
                $params[$key] = $v;
            }
        }

        if ($v = $input->getOption('label')) {
            $params['labels'] = implode(',', $v);
        }

        if ($v = $input->getOption('since')) {
            $ts = strtotime($v);

            if (false === $ts) {
                throw new \InvalidArgumentException($v . ' is not a valid date');
            }

            $params['since'] = date('c', $ts);
        }

        $issues = $paginator->fetchAll(
            $client->api('issue'),
            'all',
            [$org, $repo, $params]
        );

        // post filter
        foreach ($issues as $i => &$issue) {
            $isPr = isset($issue['pull_request']['html_url']);
            $issue['_type'] = $isPr ? 'pr' : 'issue';

            if ($v = $input->getOption('type')) {
                $this->validateEnum('type', $v);

                if ($v == 'pr' && false === $isPr) {
                    unset($issues[$i]);
                } elseif ($v == 'issue' && true === $isPr) {
                    unset($issues[$i]);
                }
            }
        }

        $table = $this->getHelper('table');
        $table->setHeaders(['#', 'State', 'PR?', 'Title', 'User', 'Assignee', 'Milestone', 'Labels', 'Created']);

        $table->formatRows($issues, function ($issue) {
            $labels = array_map(function ($label) {
                return $label['name'];
            }, $issue['labels']);

            return [
                $issue['number'],
                $issue['state'],
                $issue['_type'] == 'pr' ? 'PR' : '',
                $this->getHelper('text')->truncate($issue['title'], 40),
                $issue['user']['login'],
                $issue['assignee']['login'],
                $this->getHelper('text')->truncate($issue['milestone']['title'], 15),
                $this->getHelper('text')->truncate(implode(',', $labels), 30),
                date('Y-m-d', strtotime($issue['created_at'])),
            ];
        });

        $table->setFooter(sprintf('%s issues', count($issues)));

        $table->render($output, $table);
    }
}
