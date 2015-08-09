<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Issue;

use Gush\Adapter\SupportsDynamicLabels;
use Gush\Command\BaseCommand;
use Gush\Feature\IssueTrackerRepoFeature;
use Gush\Feature\TableFeature;
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class LabelIssuesCommand extends BaseCommand implements TableFeature, IssueTrackerRepoFeature
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
     * {@inheritdoc}
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

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        $isOnlyPullRequest = $pullRequests && !$issues;
        $isOnlyIssue = $issues && !$pullRequests;

        $params = ['state' => 'open'];

        if ($input->getOption('new')) {
            $filename = sprintf(
                '%s/.last_%s-%s_%s_sync',
                $this->getConfig()->get('home'),
                $org,
                $repo,
                $pullRequests ? 'pr' : 'issues'
            );

            if (file_exists($filename)) {
                $params['since'] = date('"Y-m-d\TH:i:s\Z"', filemtime($filename));
            }

            touch($filename);
        }

        $tracker = $this->getIssueTracker();
        $issues = $tracker->getIssues($params);
        $labelNames = $tracker->getLabels();

        $supportDynamic = $tracker instanceof SupportsDynamicLabels;
        $new = $input->getOption('new') ? 'new' : 'existing';

        if (!$issues) {
            $styleHelper->success(sprintf('No %s issues/pull-requests found.', $new));

            return self::COMMAND_SUCCESS;
        }

        if (!$labelNames && !$supportDynamic) {
            $styleHelper->error('No Labels found for assigning.');

            return self::COMMAND_FAILURE;
        }

        $validation = function ($label) use ($labelNames, $supportDynamic) {
            return $this->validateLabels($label, $labelNames, $supportDynamic);
        };

        $styleHelper->title(sprintf('Assign labels to %s issues/pull-requests.', $new));
        $styleHelper->text('This command helps you with assigning labels to new/existing issues/pull-request.');
        $styleHelper->text('If you do not type any new labels the existing ones will be used.');
        $styleHelper->newLine();
        $styleHelper->caution(
            [
                'If you "update" the labels of an issue/pull-request only those labels will be used!',
                'Any labels that were already assigned but are not selected when updating will be removed.',
            ]
        );

        $styleHelper->section(sprintf('Issues/Pull-requests (%d total)', count($issues)));

        $issueTitleFormat = ' <comment><info>#%s</info> %s</comment>';

        foreach ($issues as $issue) {
            if ($isOnlyPullRequest && $issue['pull_request']) {
                continue;
            }

            if ($isOnlyIssue && $issue['pull_request']) {
                continue;
            }

            $styleHelper->writeln(sprintf($issueTitleFormat, $issue['number'], $issue['title']));
            $styleHelper->newLine();

            $styleHelper->writeln(' <info>Current labels: </info>');
            $this->getIssueLabels($issue['labels'], $styleHelper);

            $styleHelper->writeln(' <info>Available labels: </info>');
            $this->getIssueLabels($labelNames, $styleHelper);

            $labels = $styleHelper->askQuestion(
                (new Question('<comment>Assign label(s)</comment> ', implode(', ', $issue['labels'])))
                    ->setValidator($validation)
                    ->setAutocompleterValues($labelNames)
            );

            // Sort to ensure they can be equal
            sort($labels);
            sort($issue['labels']);

            if ($labels !== $issue['labels']) {
                $tracker->updateIssue($issue['number'], ['labels' => $labels]);
                $styleHelper->success(sprintf('Updated issue/pull-request #%d.', $issue));
            }
        }

        $styleHelper->success('Issues/pull requests are updated.');

        return self::COMMAND_SUCCESS;
    }

    private function validateLabels($input, array $acceptedLabels, $supportDynamic)
    {
        $inputLabels = array_map('trim', explode(',', $input));
        $labels = [];

        foreach ($inputLabels as $label) {
            if ('' === $label) {
                continue;
            }

            if (!$supportDynamic && !in_array($label, $acceptedLabels, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Label "%s" is not accepted, use a comma to separate labels like "L-1, L-3".', $label)
                );
            }

            $labels[] = $label;
        }

        return $labels;
    }

    private function getIssueLabels(array $labels, StyleHelper $style)
    {
        if (0 === count($labels)) {
            $style->writeln(' N/A');
            $style->newLine();

            return;
        }

        $style->listing($labels);
    }
}
