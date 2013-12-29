<?php

/*
 * This file is part of the Manager Tools.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ManagerTools\Command;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Label issues and pull requests
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class LabelIssuesCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('label')
            ->setDescription('List of the issue\'s labels')
            ->addArgument('org', InputArgument::OPTIONAL, 'Name of the GitHub organization', $this->getVendorName())
            ->addArgument('repo', InputArgument::OPTIONAL, 'Name of the GitHub repository', $this->getRepoName())
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
        $organization = $input->getArgument('org');
        $repository = $input->getArgument('repo');

        $isOnlyPullRequest = $input->getOption('pull-requests') && !$input->getOption('issues');
        $isOnlyIssue = $input->getOption('issues') && !$input->getOption('pull-requests');

        $params = [
            "state" => "open"
        ];

        if ($input->getOption('new')) {
            $filename = sprintf(
                $this->getParameter('github.cache_folder') . '/.last_%s-%s_%s_sync',
                $organization,
                $repository,
                $input->getOption('pull-requests') ? 'pr' : 'issues'
            );

            if (file_exists($filename)) {
                $params['since'] = date('"Y-m-d\TH:i:s\Z"', filemtime($filename));
            }

            touch($filename);
        }

        $client = $this->getGithubClient();
        $issues = $client->api('issue')->all($organization, $repository, $params);
        $labels = $client->api('issue')->labels()->all($organization, $repository);

        if (!$issues) {
            $new = $input->getOption('new') ? 'new ' : '';
            $output->writeln(sprintf('<error>No %sissues/pull requests founded</error>', $new));
            return;
        }

        if (!$labels) {
            $output->writeln('<error>No Labels founded.</error>');
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
            $this->showLabels($output, $labelsName);

            $validation = function ($label) use ($labelsName) {
                if (!in_array($label, array_values($labelsName))) {
                    throw new \InvalidArgumentException(sprintf('Label "%s" is invalid.', $label));
                }

                return $label;
            };

            /** @var DialogHelper $dialog */
            $dialog = $this->getApplication()->getHelperSet()->get('dialog');
            $label = $dialog->askAndValidate(
                $output,
                '<comment>Label?</comment> ',
                $validation,
                false,
                null,
                $labelsName
            );

            // update the issue
            $client->api('issue')->update($organization, $repository, $issue['number'], array('labels' => $label));
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

        $table->setRow(null, $labels);

        $table->render($output);
    }
}
