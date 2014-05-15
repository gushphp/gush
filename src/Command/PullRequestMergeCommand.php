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

use Gush\Exception\AdapterException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;

/**
 * Merges a pull request
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestMergeCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:merge')
            ->setDescription('Merges the pull request given')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull Request number')
            ->addOption(
                'no-comments',
                null,
                InputOption::VALUE_NONE,
                'Avoid adding PR comments to the merge commit message'
            )
            ->addOption('remote', null, InputOption::VALUE_OPTIONAL, 'Remote to push the notes to', 'origin')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command merges the pull request given:

    <info>$ gush %command.name% 12</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $prNumber = $input->getArgument('pr_number');

        $adapter = $this->getAdapter();
        $pr      = $adapter->getPullRequest($prNumber);
        $commits = $adapter->getPullRequestCommits($prNumber);

        $message = $this->render(
            'merge',
            [
                'baseBranch' => $pr['base']['label'],
                'prTitle' => $pr['title'],
                'prBody' => $pr['body'],
                'commits' => $this->getCommitsString($commits),
            ]
        );

        try {
            $merge = $adapter->mergePullRequest($prNumber, $message);
            if (!$input->getOption('no-comments')) {
                $comments = $adapter->getComments($prNumber);
                $this->addCommentsToMergeCommit($comments, $merge, $input->getOption('remote'));
            }
            $output->writeln('Pull Request successfully merged.');

            return self::COMMAND_SUCCESS;
        } catch (AdapterException $e) {
            $output->writeln('There was a problem merging: '.$e->getMessage());

            return self::COMMAND_FAILURE;
        }
    }

    private function addCommentsToMergeCommit($comments, $sha, $remote)
    {
        if (0 === count($comments)) {
            return;
        }

        $commentText = '';
        foreach ($comments as $comment) {
            $commentText .= $this->render(
                'comment',
                [
                    'login' => $comment['user'],
                    'created_at' => $comment['created_at']->format('Y-m-d H:i'),
                    'body' => $comment['body'],
                ]
            );
        }

        $commands = [
            [
                'line' => 'git remote update',
                'allow_failures' => true
            ],
            [
                'line' => sprintf('git checkout %s', $sha),
                'allow_failures' => true
            ],
            [
                'line' => [
                    'git',
                    'notes',
                    '--ref=github-comments',
                    'add',
                    sprintf('-m%s', addslashes($commentText)),
                    $sha
                ],
                'allow_failures' => true
            ],
            [
                'line' => sprintf('git push %s refs/notes/github-comments', $remote),
                'allow_failures' => true
            ],
            [
                'line' => 'git checkout @{-1}',
                'allow_failures' => true
            ],
        ];

        $this->getHelper('process')->runCommands($commands);
    }

    private function getCommitsString($commits)
    {
        $commitsString = '';
        foreach ($commits as $commit) {
            $commitsString .= sprintf(
                "%s %s %s\n",
                $commit['sha'],
                $commit['message'],
                $commit['user']
            );
        }

        return $commitsString;
    }
}
