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
        $latestTag = $this->getHelper('git')->runGitCommand('git describe --abbrev=0 --tags');

        $commits = $this->getHelper('git')->runGitCommand(
            sprintf('git log %s...HEAD --oneline', $latestTag)
        );

        // Filter commits that reference an issue
        $issues = [];

        $adapter = $this->getAdapter();

        foreach (explode("\n", $commits) as $commit) {
            if (preg_match('/\/([0-9]+)/i', $commit, $matchesGush) && isset($matchesGush[1])) {
                $issues[] = $matchesGush[1];
            }

            if (preg_match('/[close|closes|fix|fixes] #([0-9]+)/i', $commit, $matchesGithub)
                && isset($matchesGithub[1])
            ) {
                $issues[] = $matchesGithub[1];
            }
        }

        sort($issues);

        foreach ($issues as $id) {
            $issue = $adapter->getIssue($id);

            $output->writeln(
                sprintf("%s: %s   <info>%s</info>", $id, $issue['title'], $issue['html_url'])
            );
        }

        return self::COMMAND_SUCCESS;
    }
}
