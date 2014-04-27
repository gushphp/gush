<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Gush\Feature\TableFeature;
use Gush\Feature\GitHubFeature;
use Gush\Helper\GitRepoHelper;

/**
 * Lists the issues
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class IssueListCommand extends BaseCommand implements TableFeature, GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:list')
            ->setDescription('List issues')
            ->addOption('label', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Specify a label')
            ->addOption('milestone', null, InputOption::VALUE_REQUIRED, '')
            ->addOption(
                'assignee',
                null,
                InputOption::VALUE_OPTIONAL,
                'Can be the name of a user. Pass in none for issues with no assigned user',
                false
            )
            ->addOption('creator', null, InputOption::VALUE_OPTIONAL, 'The user that created the issue.', false)
            ->addOption('mentioned', null, InputOption::VALUE_OPTIONAL, 'A user that’s mentioned in the issue.', false)
            ->addOption('state', null, InputOption::VALUE_REQUIRED, GitRepoHelper::formatEnum('issue', 'state'))
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, GitRepoHelper::formatEnum('issue', 'sort'))
            ->addOption('direction', null, InputOption::VALUE_REQUIRED, GitRepoHelper::formatEnum('issue', 'direction'))
            ->addOption('type', null, InputOption::VALUE_REQUIRED, GitRepoHelper::formatEnum('issue', 'type'))
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only issues after this time are displayed.')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists issues from either the current or the given organization
and repository:

    <info>$ php %command.full_name%</info>
    <info>$ php %command.full_name% --creator --sort=created --direction=desc --since="6 months ago"
    --type=pr</info>

All of the parameters provided by the github API are supported:

    https://developer.github.com/v3/issues/#list-issues-for-a-repository

With the addition of the <info>--type</info> option which enables you to filter show only pull-requests or only issues.
EOF
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getTableDefaultLayout()
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = $this->getAdapter();
        $params = GitRepoHelper::validateEnums($input, 'issue', ['state', 'sort', 'direction']);
        $username = $this->getParameter('authentication')['username'];
        $options = ['creator', 'assignee', 'mentioned'];

        foreach ($options as $option) {
            $parameterOption = $input->getParameterOption('--'.$option);
            if (null === $parameterOption) {
                $params[$option] = $username;
            } else if (false !== $parameterOption) {
                $params[$option] = $parameterOption;
            }
        }

        $params['milestone'] = $input->getOption('milestone');

        if ($label = $input->getOption('label')) {
            $params['labels'] = implode(',', $label);
        }

        if ($since = $input->getOption('since')) {
            $timeStamp = strtotime($since);

            if (false === $timeStamp) {
                throw new \InvalidArgumentException($since . ' is not a valid date');
            }

            $params['since'] = date('c', $timeStamp);
        }

        $issues  = $adapter->getIssues($params);

        // post filter
        foreach ($issues as $i => &$issue) {
            $isPr = isset($issue['pull_request']['html_url']);
            $issue['_type'] = $isPr ? 'pr' : 'issue';

            if ($type = $input->getOption('type')) {
                GitRepoHelper::validateEnum('issue', 'type', $type);

                if ($type == 'pr' && false === $isPr) {
                    unset($issues[$i]);
                } elseif ($type == 'issue' && true === $isPr) {
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

        return self::COMMAND_SUCCESS;
    }
}
