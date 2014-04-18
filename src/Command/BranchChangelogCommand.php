<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;

/**
 * Reports what got fixed or closed since last release on current branch
 *
 * @author Luis Cordova <cordoval@gmail.com>
 *
 * adapted from lornajane and sebastianbergmann
 * reference: http://www.lornajane.net/posts/2014/github-powered-changelog-scripts
 */
class BranchChangelogCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:changelog')
            ->setDescription('Reports what got fixed or closed since last release on current branch')
            ->addOption(
                'include-in-progress',
                null,
                InputOption::VALUE_NONE,
                'Include in-progress issues (open, but has commit)'
            )
            ->addOption(
                'log-format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Log format, check the GitHub issue API for valid tokens',
                '#%number%: %title%   <info>%html_url%</info>'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command :

    <info>$ gush %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $latestTag = $this->getHelper('git')->runGitCommand('git describe --abbrev=0 --tags');
        } catch (\RuntimeException $e) {
            $output->writeln('<info>There were no tags found</info>');

            return self::COMMAND_SUCCESS;
        }

        $commits = $this->getHelper('git')->runGitCommand(
            sprintf('git log %s...HEAD --format="%s"', $latestTag, "%s%b")
        );

        // Filter commits that reference an issue
        $issues = [];

        $adapter = $this->getAdapter();

        foreach (explode("\n", $commits) as $commit) {
            // Cut issue id from branch name (merge commits)
            if (preg_match('/\/([0-9]+)/i', $commit, $matchesGush) && isset($matchesGush[1])) {
                $issues[$matchesGush[1]] = $matchesGush[1];
            }

            // Cut issue id from commit message
            if (preg_match('/#([0-9]+)/i', $commit, $matchesGithub)
                && isset($matchesGithub[1])
            ) {
                $issues[$matchesGithub[1]] = $matchesGithub[1];
            }
        }

        sort($issues);

        foreach ($issues as $id) {
            $issue = $adapter->getIssue($id);

            if ($issue['state'] === 'closed' || $input->getOption('include-in-progress')) {
                $output->writeln($this->getLogLine($input->getOption('log-format'), $issue));
            }
        }

        return self::COMMAND_SUCCESS;
    }

    private function getLogLine($format, array $issue)
    {
        $issue = $this->flattenIssue($issue);

        return preg_replace_callback('/%([^%]*)%/', function ($matches) use ($issue) {
            $token = $matches[1];

            return isset($issue[$token]) ? $issue[$token] : '';
        }, $format);
    }

    private function flattenIssue(array $issue, $prefix = '')
    {
        $result = [];

        foreach ($issue as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flattenIssue($value, $prefix . $key . '.');
            } else {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }
}
