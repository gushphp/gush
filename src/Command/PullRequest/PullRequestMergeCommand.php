<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Exception\AdapterException;
use Gush\Exception\CannotSquashMultipleAuthors;
use Gush\Exception\UnknownRemoteException;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Gush\Operation\RemoteMergeOperation;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull Request number')->addArgument(
                'pr_type',
                InputArgument::OPTIONAL,
                'Pull Request type eg. bug, feature (default is merge)',
                'merge'
            )
            ->addOption(
                'no-comments',
                null,
                InputOption::VALUE_NONE,
                'Avoid adding PR comments to the merge commit message'
            )
            ->addOption(
                'squash',
                null,
                InputOption::VALUE_NONE,
                'Squash the PR before merging'
            )
            ->addOption(
                'force-squash',
                null,
                InputOption::VALUE_NONE,
                'Force squashing the PR, even if there are multiple authors (this will implicitly use --squash)'
            )
            ->addOption(
                'switch',
                null,
                InputOption::VALUE_REQUIRED,
                'Switch the base of the pull request before merging'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command merges the given pull request:

    <info>$ gush %command.name% 12</info>

Optionally you can prefix the merge title with a type like: bug, feature or anything you like.
<comment>Using a type makes it easier to search for a such a PR-type in your git history.</comment>

    <info>$ gush %command.name% 12 bug</info>

If there are many unrelated commits (like cs fixes) you can squash all the commits in the
pull-request into one big commit using:

    <info>$ gush %command.name% --squash 12</info>

This will use the message-body and author of the first commit in the pull-request.

<comment>Note:</comment> Squashing a PR requires that all the commits in the pull-request were done by one author.
You can overwrite this behaviour with <comment>--force-squash</comment>

If the pull request was opened against the master branch as target, but you rather want to merge it into another branch,
like "development" you can use <comment>--switch</comment> to change the base when merging.

<comment>This will only merge the commits that are in the source branch but not in the original target branch!</comment>

    <info>$ gush %command.name% --switch=development 12</info>
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
        $squash = $input->getOption('squash') || $input->getOption('force-squash');

        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);

        if (false === $this->guardPullRequestMerge($pr, $output)) {
            return self::COMMAND_FAILURE;
        }

        $gitHelper = $this->getHelper('git');
        /** @var GitHelper $gitHelper */

        $sourceRemote = $pr['head']['user'];
        $sourceRepository = $pr['head']['repo'];
        $sourceBranch = $pr['head']['ref'];

        $targetRemote = $pr['base']['user'];
        $targetRepository = $pr['base']['repo'];
        $targetBranch = $pr['base']['ref'];

        $this->ensureRemoteExists($targetRemote, $targetRepository, $output);
        $this->ensureRemoteExists($sourceRemote, $sourceRepository, $output);

        try {
            $mergeNote = $this->getMergeNote($pr, $squash, $input->getOption('switch'));
            $messageCallback = function ($base, $tempBranch) use ($prType, $pr, $prNumber, $mergeNote, $gitHelper) {
                return $this->render(
                    'merge',
                    [
                        'type' => $prType,
                        'author' => $pr['user'],
                        'prNumber' => $pr['number'],
                        'prTitle' => trim($pr['title']),
                        'mergeNote' => $mergeNote,
                        'prBody' => trim($pr['body']),
                        'commits' => $this->getCommitsString($gitHelper->getLogBetweenCommits($base, $tempBranch)),
                    ]
                );
            };

            $mergeOperation = $gitHelper->createRemoteMergeOperation();
            $mergeOperation->setTarget($targetRemote, $targetBranch);
            $mergeOperation->setSource($sourceRemote, $sourceBranch);
            $mergeOperation->squashCommits($squash, $input->getOption('force-squash'));
            $mergeOperation->switchBase($input->getOption('switch'));
            $mergeOperation->setMergeMessage($messageCallback);

            $mergeCommit = $mergeOperation->performMerge();
            $mergeOperation->pushToRemote();

            if (!$input->getOption('no-comments')) {
                $this->addCommentsToMergeCommit(
                    $adapter->getComments($prNumber),
                    $mergeCommit,
                    $targetRemote
                );
            }

            $adapter->closePullRequest($prNumber);
            $output->writeln($mergeNote);

            return self::COMMAND_SUCCESS;
        } catch (CannotSquashMultipleAuthors $e) {
            $output->writeln(
                "<error>\n[ERROR] Can not squash commits when there are multiple authors.".
                "Use --force-squash to continue or ask the author to squash commits manually.</error>"
            );

            return self::COMMAND_FAILURE;
        } catch (\Exception $e) {
            $output->writeln('<error>There was a problem merging: </error> '.$e->getMessage());

            return self::COMMAND_FAILURE;
        }
    }

    private function ensureRemoteExists($org, $repo, OutputInterface $output)
    {
        $gitConfigHelper = $this->getHelper('git_config');
        /** @var GitConfigHelper $gitConfigHelper */

        $adapter = $this->getAdapter();
        $repoInfo = $adapter->getRepositoryInfo($org, $repo);

        if (!$gitConfigHelper->remoteExists($org, $repoInfo['fetch_url'])) {
            $output->writeln(
                sprintf(
                    "<info>\n[INFO] Adding remote '%s' with '%s' to git local config.</info>",
                    $org,
                    $repoInfo['fetch_url']
                )
            );

            $gitConfigHelper->setRemote($org, $repoInfo['fetch_url'], $repoInfo['push_url']);
        }
    }

    private function guardPullRequestMerge(array $pr, OutputInterface $output)
    {
        if ('open' !== $pr['state']) {
            $output->writeln(
                sprintf(
                    "<error>\n[ERROR] Pull request #%s is already merged/closed, current status: %s</error>",
                    $pr['number'],
                    $pr['state']
                )
            );

            return false;
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

        $gitHelper = $this->getHelper('git');
        /** @var GitHelper $gitHelper */

        $gitHelper->remoteUpdate($remote);
        $gitHelper->addNotes($commentText, $sha, 'github-comments');
        $gitHelper->pushToRemote($remote, 'refs/notes/github-comments');
    }

    private function getMergeNote(array $pr, $squash = false, $newBase = null)
    {
        if ($newBase === $pr['base']['ref']) {
            $newBase = null;
        }

        $template = 'merge_note_';
        $params = [
            'prNumber' => $pr['number'],
            'baseBranch' => $pr['base']['ref'],
            'originalBaseBranch' => $pr['base']['ref'],
            'targetBaseBranch' => $newBase,
        ];

        if (null !== $newBase) {
            $template .= 'switched_base';

            if ($squash) {
                $template .= '_and_squashed';
            }
        } elseif ($squash) {
            $template .= 'squashed';
        } else {
            $template .= 'normal';
        }

        return $this->render($template, $params);
    }

    private function getCommitsString(array $commits)
    {
        $commitsString = '';

        foreach ($commits as $commit) {
            $commitsString .= sprintf(
                "%s %s\n",
                $commit['sha'],
                $commit['subject']
            );
        }

        return $commitsString;
    }
}
