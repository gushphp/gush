<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Branch;

use Gush\Command\BaseCommand;
use Gush\Feature\IssueTrackerRepoFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BranchChangelogCommand extends BaseCommand implements IssueTrackerRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:changelog')
            ->setDescription('Reports what got fixed or closed since last release on current branch.')
            ->setHelp(
                <<<EOF
Reports what got fixed or closed since last release on current branch.
reference: http://www.lornajane.net/posts/2014/github-powered-changelog-scripts

    <info>$ gush %command.name%</info>

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
            $latestTag = $this->getHelper('git')->getLastTagOnBranch();
        } catch (\RuntimeException $e) {
            $this->getHelper('gush_style')->note(
                sprintf('No tags were found on branch "%s".', $this->getHelper('git')->getActiveBranchName())
            );

            return self::COMMAND_SUCCESS;
        }

        $adapter = $this->getIssueTracker();
        $commits = $this->getHelper('git')->getLogBetweenCommits($latestTag, 'HEAD');
        $issues = $this->getIssuesFromCommits($commits);

        foreach ($issues as $id) {
            // ignore missing issues
            try {
                $issue = $adapter->getIssue($id);
            } catch (\Exception $e) {
                continue;
            }

            $output->writeln(
                sprintf("#%s: %s   <info>%s</info>", $id, $issue['title'], $issue['url'])
            );
        }

        return self::COMMAND_SUCCESS;
    }

    /**
     * @param array $commits
     *
     * @return integer[]
     */
    private function getIssuesFromCommits(array $commits)
    {
        $issues = [];

        foreach ($commits as $commit) {
            if (preg_match('{#([0-9]+)}i', $commit['message'], $matchesGush)) {
                $issues[] = $matchesGush[1];
            }
        }

        $issues = array_unique($issues);
        sort($issues);

        return $issues;
    }
}
