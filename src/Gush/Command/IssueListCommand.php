<?php

/*
 * This file is part of the Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Lists the issues
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class IssueListCommand extends BaseCommand
{
    protected $enum = array(
        'filter' => array(
            'assigned',
            'created',
            'mentioned',
            'subscribed',
            'all',
        ),
        'state' => array(
            'open',
            'closed',
        ),
        'sort' => array(
            'created',
            'updated',
        ),
        'direction' => array('asc', 'desc'),
        'type' => array('pr', 'issue'),
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:list')
            ->setDescription('List issues')
            ->addArgument('org', InputArgument::OPTIONAL, 'Name of the GitHub organization', $this->getVendorName())
            ->addArgument('repo', InputArgument::OPTIONAL, 'Name of the GitHub repository', $this->getRepoName())
            ->addOption('label', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Specify a label')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('filter'))
            ->addOption('state', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('state'))
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('sort'))
            ->addOption('direction', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('direction'))
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only issues after this time are displayed.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('type'))
            ->setHelp(<<<HERE
The <info>%command.name%</info> command lists issues from either the current or the given organization
and repository:

    <info>$ php %command.full_name%</info>
    <info>$ php %command.full_name% --filter=created --sort=created --direction=desc --since="6 months ago" --type=pr</info>

All of the parameters provided by the github API are supported:

    http://developer.github.com/v3/issues/#list-issues

With the addition of the <info>--type</info> option which enables you to filter show only pull-requests or only issues.
HERE
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $organization = $input->getArgument('org');
        $repository = $input->getArgument('repo');

        $client = $this->getGithubClient();

        $params = array();

        foreach (array('state', 'filter', 'sort', 'direction') as $key) {
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

        $starttime = microtime(true);

        $issues = $client->api('issue')->all($organization, $repository, $params);

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

        /** @var TableHelper $table */
        $table = $this->getApplication()->getHelperSet()->get('table');
        $table->setHeaders(array(
            '#', 'State', 'PR?', 'Title', 'User', 'Assignee', 'Milestone', 'Labels', 'Created',
        ));

        foreach ($issues as $issue) {
            $labels = array();
            foreach ($issue['labels'] as $label) {
                $labels[] = $label['name'];
            }

            $table->addRow(array(
                $issue['number'],
                $issue['state'],
                $issue['_type'] == 'pr' ? 'PR' : '',
                substr($issue['title'], 0, 50) . (strlen($issue['title']) > 50 ? '..' : ''),
                $issue['user']['login'],
                $issue['assignee']['login'],
                $issue['milestone']['title'],
                implode(',', $labels),
                date('Y-m-d', strtotime($issue['created_at'])),
            ));
        }

        $table->render($output);

        $output->writeln('');
        $elapsedtime = microtime(true) - $starttime;
        $output->writeln(sprintf('%s issues in %ss',
            count($issues), number_format($elapsedtime, 2)
        ));
    }
}
