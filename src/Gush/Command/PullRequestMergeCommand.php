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
            ->addOption('no-comments', null, InputOption::VALUE_NONE, 'Avoid adding PR comments to the merge commit message')
            ->addOption('remote', null, InputOption::VALUE_OPTIONAL, 'Remote to push the notes to', 'origin')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command merges the pull request given:

    <info>$ gush %command.full_name% 12</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');
        $prNumber = $input->getArgument('pr_number');

        $client = $this->getGithubClient();

        $pr = $client->api('pull_request')->show($org, $repo, $prNumber);
        $commits = $client->api('pull_request')->commits($org, $repo, $prNumber);

        $message = $this->render(
            'merge',
            [
                'baseBranch' => $pr['base']['label'],
                'prTitle' => $pr['title'],
                'prBody' => $pr['body'],
                'commits' => $this->getCommitsString($commits),
            ]
        );

        $merge = $client->api('pull_request')->merge($org, $repo, $prNumber, $message);

        if ($merge['merged']) {
            if (!$input->getOption('no-comments')) {
                $comments = $client->api('issues')->comments()->all($org, $repo, $prNumber);
                $this->addCommentsToMergeCommit($comments, $merge['sha'], $input->getOption('remote'));
            }
            $output->writeln($merge['message']);
        } else {
            $output->writeln('There was a problem merging: '.$merge['message']);
        }

        return self::COMMAND_SUCCESS;
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
                    'login' => $comment['user']['login'],
                    'created_at' => $comment['created_at'],
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
                $commit['commit']['message'],
                $commit['author']['login']
            );
        }

        return $commitsString;
    }
}
