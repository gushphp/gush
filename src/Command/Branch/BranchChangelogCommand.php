<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Branch;

use Gush\Command\BaseCommand;
use Gush\Feature\GitFolderFeature;
use Gush\Feature\IssueTrackerRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BranchChangelogCommand extends BaseCommand implements IssueTrackerRepoFeature, GitFolderFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:changelog')
            ->setDescription('Reports what got fixed or closed since last release on the given branch')
            ->addArgument(
                'branch',
                InputArgument::OPTIONAL,
                'Branch to look for tags in. When unspecified, the current branch is used'
            )
            ->addOption(
                'search',
                's',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Regex pattern to use for searching',
                ['/#(?P<id>[0-9]+)/i']
            )
            ->setHelp(
                <<<EOF
Reports what got fixed or closed since the last release on the given branch.

    <info>$ gush %command.name%</info>

This command will search all the commits in the given branch (that were made after the last tag)
and will try to extract the issue numbers from the message. To only match a precise pattern, use the
<comment>--search</comment> option to specify one or multiple regex-patterns (with delimiters and flags).

For example, if your issues are prefixed with "DC-", use the following:

    <info>$ gush %command.name% --search="{DC-(?P<id>[0-9]+)}i"</info>

Note: It's important the regex has a "named capturing group" like <comment>(?P<id>[0-9]+)</comment>.
This named group must (only) match the issue number and nothing else.

To learn more about composing your own regex patterns see:
http://php.net/manual/reference.pcre.pattern.syntax.php
http://www.regular-expressions.info/
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branch = $input->getArgument('branch') ?: $this->getHelper('git')->getActiveBranchName();

        try {
            $latestTag = $this->getHelper('git')->getLastTagOnBranch($branch);
        } catch (\RuntimeException $e) {
            $this->getHelper('gush_style')->note(
                sprintf('No tags were found on branch "%s".', $branch)
            );

            return self::COMMAND_SUCCESS;
        }

        $adapter = $this->getIssueTracker();
        $commits = $this->getHelper('git')->getLogBetweenCommits($latestTag, $branch);
        $issues = $this->getIssuesFromCommits($commits, $input->getOption('search'));

        foreach ($issues as $id => $idLabel) {
            // ignore missing issues
            try {
                $issue = $adapter->getIssue($id);
            } catch (\Exception $e) {
                continue;
            }

            $output->writeln(
                sprintf('%s: %s   <info>%s</info>', $idLabel, $issue['title'], $issue['url'])
            );
        }

        return self::COMMAND_SUCCESS;
    }

    private function getIssuesFromCommits(array $commits, array $searchPatterns)
    {
        $issues = [];

        foreach ($commits as $commit) {
            foreach ($searchPatterns as $regex) {
                if (preg_match($regex, $commit['message'], $matchesGush) && isset($matchesGush['id'])) {
                    $issues[$matchesGush['id']] = $matchesGush[0];
                }
            }
        }

        ksort($issues);

        return $issues;
    }
}
