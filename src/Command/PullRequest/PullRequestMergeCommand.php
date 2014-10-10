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
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

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

Optionally you can prefix the merge title with a type like: bug, feature or anything you like.
<comment>Using a type makes it easier to search for a such a PR-type in your git history.</comment>

    <info>$ gush %command.name% 12 bug</info>
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
        $pr = $adapter->getPullRequest($prNumber);

        if ('open' !== $pr['state']) {
            $output->writeln(
                sprintf(
                    "<error>\n[ERROR] Pull request #%s is already merged/closed, current status: %s</error>",
                    $prNumber,
                    $pr['state']
                )
            );

            return;
        }

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

        $gitHelper = $this->getHelper('git');
        /** @var GitHelper $gitHelper */

        try {
            $sourceRemote = $pr['head']['user'];
            $baseRemote = $input->getOption('remote');

            $base = $pr['base']['ref'];
            $sourceBranch = $pr['head']['ref'];

            $mergeCommit = $gitHelper->mergeRemoteBranch(
                $sourceRemote,
                $baseRemote,
                $base,
                $sourceBranch,
                $message
            );

            if (!$input->getOption('no-comments')) {
                $this->addCommentsToMergeCommit(
                    $adapter->getComments($prNumber),
                    $mergeCommit,
                    $input->getOption('remote')
                );
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

        $fs = new Filesystem();
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gush';

        if (!file_exists($dir)) {
            $fs->mkdir($dir);
        }

        $tmpName = tempnam($dir, '');
        file_put_contents($tmpName, $commentText);

        $commands = [
            [
                'line' => 'git remote update',
                'allow_failures' => true,
            ],
            [
                'line' => [
                    'git',
                    'notes',
                    '--ref=github-comments',
                    'add',
                    '-F',
                    $tmpName,
                    $sha,
                ],
                'allow_failures' => true,
            ],
            [
                'line' => sprintf('git push %s refs/notes/github-comments', $remote),
                'allow_failures' => true,
            ],
        ];

        $this->getHelper('process')->runCommands($commands);
        $fs->remove($tmpName);
    }

    private function getCommitsString($commits)
    {
        $commitsString = '';
        foreach ($commits as $commit) {
            // Only use the first line
            if (strpos($commit['message'], PHP_EOL)) {
                $commit['message'] = explode(PHP_EOL, $commit['message'])[0];
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
