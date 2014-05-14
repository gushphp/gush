<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Issue;

use Gush\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Gush\Feature\TableFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitRepoHelper;

/**
 * Lists the issues
 *
 * @author Daniel Leech <daniel@dantleech.com>
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class IssueListCommand extends BaseCommand implements TableFeature, GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:list')
            ->setDescription('List issues')
            ->addOption('label', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY)
            ->addOption('milestone', null, InputOption::VALUE_REQUIRED)
            ->addOption('assignee', null, InputOption::VALUE_REQUIRED, 'Username assignee. None for unassigned.')
            ->addOption('creator', null, InputOption::VALUE_REQUIRED, 'The user that created the issue.')
            ->addOption('mentioned', null, InputOption::VALUE_REQUIRED, 'The user mentioned in the issue.')
            ->addOption('state', null, InputOption::VALUE_REQUIRED, GitRepoHelper::formatEnum('issue', 'state'))
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, GitRepoHelper::formatEnum('issue', 'sort'))
            ->addOption('direction', null, InputOption::VALUE_REQUIRED, GitRepoHelper::formatEnum('issue', 'direction'))
            ->addOption('type', null, InputOption::VALUE_REQUIRED, GitRepoHelper::formatEnum('issue', 'type'))
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only issues after this time are displayed.')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists issues from either the current or the given organization
and repository:

    <info>$ php %command.name%</info>
    <info>$ php %command.name% --creator=cordoval --sort=created --direction=desc --since="6 months ago"
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

        foreach (['creator', 'assignee', 'mentioned', 'milestone'] as $option) {
            $params[$option] = $input->getOption($option);
        }

        if ($label = $input->getOption('label')) {
            $params['labels'] = implode(',', $label);
        }

        if ($since = $input->getOption('since')) {
            $timeStamp = strtotime($since);

            if (false === $timeStamp) {
                throw new \InvalidArgumentException($since.' is not a valid date');
            }

            $params['since'] = date('c', $timeStamp);
        }

        $issues  = $adapter->getIssues($params);

        // post filter
        foreach ($issues as $i => &$issue) {
            $isPr = isset($issue['pull_request']);
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
        $table->setHeaders(['#', 'State', 'PR?', 'Title', 'User', 'Assignee', 'Milestone', 'Labels', 'Created', 'Link']);

        $table->formatRows($issues, function ($issue) {
            return [
                $issue['number'],
                $issue['state'],
                $issue['_type'] === 'pr' ? 'PR' : '',
                $this->getHelper('text')->truncate($issue['title'], 40),
                $issue['user'],
                $issue['assignee'],
                $this->getHelper('text')->truncate($issue['milestone'], 15),
                $this->getHelper('text')->truncate(implode(',', $issue['labels']), 30),
                null !== $issue['created_at'] ? $issue['created_at']->format('Y-m-d H:i') : '',
                $issue['html_url'],
            ];
        });

        $table->setFooter(sprintf('%s issues', count($issues)));

        $table->render($output, $table);

        return self::COMMAND_SUCCESS;
    }
}
