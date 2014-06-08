<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Exception\AdapterException;
use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Merges a pull request
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestMergeCommand extends BaseCommand implements GitRepoFeature
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
            ->addArgument('pr_type', InputArgument::OPTIONAL, 'Pull Request type eg. bug, feature (default is merge)')
            ->addOption(
                'no-comments',
                null,
                InputOption::VALUE_NONE,
                'Avoid adding PR comments to the merge commit message'
            )
            ->addOption('remote', null, InputOption::VALUE_OPTIONAL, 'Remote to push the notes to', 'origin')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command merges the given pull request:

    <info>$ gush %command.name% 12</info>

Optionally you prefix can prefix the merge-commit message with a type like bug, feature.
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
        $prType = $input->getArgument('pr_type');

        $adapter = $this->getAdapter();
        $pr      = $adapter->getPullRequest($prNumber);
        $commits = $adapter->getPullRequestCommits($prNumber);

        if (null === $prType) {
            $prType = 'merge';
        }

        $message = $this->render(
            'merge',
            [
                'type' => $prType,
                'author' => $pr['user'],
                'baseBranch' => $pr['base']['label'],
                'prNumber' => $prNumber,
                'prTitle' => trim($pr['title']),
                'prBody' => trim($pr['body']),
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
            // Only use the first line
            if (strpos($commit['message'], "\n")) {
                $pr['message'] = explode("\n", $commit['message'])[0];
            }

            $commitsString .= sprintf(
                "%s %s (%s)\n",
                $commit['sha'],
                $commit['message'],
                $commit['user']
            );
        }

        return $commitsString;
    }
}
