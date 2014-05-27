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

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;
use Gush\Feature\TableFeature;

/**
 * Labels issues and pull requests
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class LabelIssuesCommand extends BaseCommand implements TableFeature, GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:label:assign')
            ->setDescription('Labels issues/pull requests')
            ->addOption('new', null, InputOption::VALUE_NONE, 'Get only new issues/pull requests')
            ->addOption('issues', null, InputOption::VALUE_NONE, 'Get issues')
            ->addOption('pull-requests', null, InputOption::VALUE_NONE, 'Get pull requests')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Label')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command labels issue or pull requests for either the current or the given organization
and repo:

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getTableDefaultLayout()
    {
        return 'compact';
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');
        $pullRequests = $input->getOption('pull-requests');
        $issues = $input->getOption('issues');

        $isOnlyPullRequest = $pullRequests && !$issues;
        $isOnlyIssue = $issues && !$pullRequests;

        $params = ['state' => 'open'];

        if ($input->getOption('new')) {
            $filename = sprintf(
                '%s/.last_%s-%s_%s_sync',
                $this->getParameter('cache-dir'),
                $org,
                $repo,
                $pullRequests ? 'pr' : 'issues'
            );

            if (file_exists($filename)) {
                $params['since'] = date('"Y-m-d\TH:i:s\Z"', filemtime($filename));
            }

            touch($filename);
        }

        $adapter = $this->getIssueTracker();
        $issues = $adapter->getIssues($params);
        $labelNames = $adapter->getLabels();

        if (!$issues) {
            $new = $input->getOption('new') ? 'new ' : '';
            $output->writeln(sprintf('<error>No %sissues/pull requests found</error>', $new));

            return self::COMMAND_FAILURE;
        }

        if (!$labelNames) {
            $output->writeln('<error>No Labels found.</error>');

            return self::COMMAND_FAILURE;
        }

        $issueTitleFormat = '<comment>[<info>#%s</info>] %s</comment>';

        foreach ($issues as $issue) {
            if ($isOnlyPullRequest && $issue['pull_request']) {
                continue;
            }

            if ($isOnlyIssue && $issue['pull_request']) {
                continue;
            }

            $output->writeln(sprintf($issueTitleFormat, $issue['number'], $issue['title']));
            $output->writeln('<info>current labels:</info> ' . $this->getIssueLabels($issue));
            $this->showLabels($output, $labelNames);

            $validation = function ($label) use ($labelNames) {
                $labels = explode(',', $label);
                foreach ($labels as $item) {
                    if (!in_array($item, array_values($labelNames))) {
                        throw new \InvalidArgumentException(sprintf('Label "%s" is invalid.', $item));
                    }
                }

                return $label;
            };

            /** @var DialogHelper $dialog */
            $dialog = $this->getApplication()->getHelperSet()->get('dialog');
            if (!$label = $input->getOption('label')) {
                $label = $dialog->askAndValidate(
                    $output,
                    '<comment>Label(s)?</comment> ',
                    $validation,
                    false,
                    null,
                    $labelNames
                );
            }

            // updates the issue
            $adapter->updateIssue($issue['number'], ['labels' => explode(',', $label)]);
        }

        return self::COMMAND_SUCCESS;
    }

    private function showLabels(OutputInterface $output, array $labels)
    {
        /** @var TableHelper $table */
        $table = $this->getHelper('table');
        $table->setRows(array_chunk($labels, 3));
        $table->render($output);
    }

    private function getIssueLabels(array $issue)
    {
        return count($issue['labels']) ? join(', ', $issue['labels']) : 'N/A';
    }
}
