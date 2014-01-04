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
        'filters' => array(
            'assigned',
            'created',
            'mentioned',
            'subscribed',
            'all',
        ),
        'states' => array(
            'open',
            'closed',
        ),
        'sortFields' => array(
            'created',
            'updated',
        ),
        'directions' => array('asc', 'desc'),
        'type' => array('pr', 'issue'),
    );

    protected function formatEnumDescription($name)
    {
        return 'One of <comment>' . implode('</comment>, <comment>', $this->enum[$name]) . '</comment>';
    }

    protected function validateEnum($name, $v)
    {
        if (!isset($this->enum[$name])) {
            throw new \InvalidArgumentException('Unknown enum ' . $name);
        }

        if (!in_array($v, $this->enum[$name])) {
            throw new \InvalidArgumentException(
                'Value must be one of ' . implode(', ', $this->enum[$name]) . ' got "' . $v . '"'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issues:list')
            ->setDescription('List issues')
            ->addArgument('org', InputArgument::OPTIONAL, 'Name of the GitHub organization', $this->getVendorName())
            ->addArgument('repo', InputArgument::OPTIONAL, 'Name of the GitHub repository', $this->getRepoName())
            ->addOption('label', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Specify a label')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('filters'))
            ->addOption('state', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('states'))
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('sortFields'))
            ->addOption('direction', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('directions'))
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only issues after this time are displayed.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, $this->formatEnumDescription('type'))
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

        if ($v = $input->getOption('label')) {
            $params['labels'] = implode(',', $v);
        }

        if ($v = $input->getOption('state')) {
            $this->validateEnum('states', $v);
            $params['state'] = $v;
        }

        if ($v = $input->getOption('filter')) {
            $this->validateEnum('filters', $v);
            $params['filter'] = $v;
        }

        if ($v = $input->getOption('sort')) {
            $this->validateEnum('sortFields', $v);
            $params['sort'] = $v;
        }

        if ($v = $input->getOption('direction')) {
            $this->validateEnum('directions', $v);
            $params['direction'] = $v;
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
        foreach ($issues as $i => $issue) {
            if ($v = $input->getOption('type')) {
                $this->validateEnum('type', $v);
                $isPr = isset($issue['pull_request']['html_url']);

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
            '#', 'State', 'Title', 'User', 'Assignee', 'Milestone', 'Labels', 'Created'
        ));

        foreach ($issues as $issue) {
            $labels = array();
            foreach ($issue['labels'] as $label) {
                $labels[] = $label['name'];
            }

            $table->addRow(array(
                $issue['number'],
                $issue['state'],
                substr($issue['title'], 0, 50) . (strlen($issue['title']) > 50 ? '..' : ''),
                $issue['user']['login'],
                $issue['assignee']['login'],
                $issue['milestone']['title'],
                implode(',', $labels),
                date('Y-m-d H:i:s', strtotime($issue['created_at'])),
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
