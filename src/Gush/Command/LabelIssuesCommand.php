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

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;
use Gush\Feature\TableFeature;

/**
 * Label issues and pull requests
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
            ->setName('label')
            ->setDescription('Label issues/pull requests of a repo')
            ->addOption('new', null, InputOption::VALUE_NONE, 'Get only new issues/pull requests')
            ->addOption('issues', null, InputOption::VALUE_NONE, 'Get issues')
            ->addOption('pull-requests', null, InputOption::VALUE_NONE, 'Get pull requests')
        ;
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

        $params = [
            "state" => "open"
        ];

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

        $client = $this->getGithubClient();
        $issues = $client->api('issue')->all($org, $repo, $params);
        $labels = $client->api('issue')->labels()->all($org, $repo);

        if (!$issues) {
            $new = $input->getOption('new') ? 'new ' : '';
            $output->writeln(sprintf('<error>No %sissues/pull requests found</error>', $new));

            return;
        }

        if (!$labels) {
            $output->writeln('<error>No Labels found.</error>');

            return;
        }

        // we only need the labels name
        $labelsName = [];
        foreach ($labels as $label) {
            $labelsName[] = $label['name'];
        }

        $issueTitleFormat = '<comment>[<info>#%s</info>] %s</comment>';

        foreach ($issues as $issue) {
            if ($isOnlyPullRequest && !isset($issue['pull_request'])) {
                continue;
            }

            if ($isOnlyIssue && isset($issue['pull_request'])) {
                continue;
            }

            $output->writeln(sprintf($issueTitleFormat, $issue['number'], $issue['title']));
            $output->writeln('<info>current labels:</info> ' . $this->getIssueLabels($issue));
            $this->showLabels($output, $labelsName);

            $validation = function ($label) use ($labelsName) {
                $labels = explode(',', $label);
                foreach ($labels as $item) {
                    if (!in_array($item, array_values($labelsName))) {
                        throw new \InvalidArgumentException(sprintf('Label "%s" is invalid.', $item));
                    }
                }

                return $label;
            };

            /** @var DialogHelper $dialog */
            $dialog = $this->getApplication()->getHelperSet()->get('dialog');
            $label = $dialog->askAndValidate(
                $output,
                '<comment>Label(s)?</comment> ',
                $validation,
                false,
                null,
                $labelsName
            );

            // update the issue
            $client->api('issue')->update($org, $repo, $issue['number'], ['labels' => explode(',', $label)]);
        }
    }

    /**
     * Outputs the Labels
     *
     * @param OutputInterface $output
     * @param array           $labels
     */
    private function showLabels(OutputInterface $output, array $labels)
    {
        /** @var TableHelper $table */
        $table = $this->getApplication()->getHelperSet()->get('table');
        $table->setLayout(TableHelper::LAYOUT_BORDERLESS);
        $table->setHorizontalBorderChar('');

        $table->setRows(array_chunk($labels, 3));

        $table->render($output);
    }

    /**
     * Retrieves the labels assigned to a given Issue
     *
     * @param  array  $issue The issue
     * @return string
     */
    private function getIssueLabels(array $issue)
    {
        $labels = [];
        foreach ($issue['labels'] as $label) {
            $labels[] = $label['name'];
        }

        return count($labels) ? join(', ', $labels) : 'N/A';
    }
}
