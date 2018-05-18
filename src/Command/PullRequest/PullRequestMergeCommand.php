<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Exception\CannotSquashMultipleAuthors;
use Gush\Exception\UserException;
use Gush\Feature\GitDirectoryFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Gush\Template\Pats\Pats;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestMergeCommand extends BaseCommand implements GitRepoFeature, GitDirectoryFeature
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
            ->addOption('no-comments', null, InputOption::VALUE_NONE, 'Avoid adding PR comments to the merge commit message')
            ->addOption('fast-forward', null, InputOption::VALUE_NONE, 'Merge pull-request using fast forward (no merge commit will be created)')
            ->addOption('squash', null, InputOption::VALUE_NONE, 'Squash the PR before merging')
            ->addOption('force-squash', null, InputOption::VALUE_NONE, 'Force squashing the PR, even if there are multiple authors (this will implicitly use --squash)')
            ->addOption('rebase', null, InputOption::VALUE_NONE, 'Rebase the PR before merging')
            ->addOption('ensure-sync', null, InputOption::VALUE_NONE, 'Ensure that the pull request history is up to date before merging')
            ->addOption('switch', null, InputOption::VALUE_REQUIRED, 'Switch the base of the pull request before merging')
            ->addOption('pat', null, InputOption::VALUE_REQUIRED, 'Give the PR\'s author a pat on the back after the merge')
            ->setHelp(
                <<<'EOF'
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

Pull-requests are merged as non fast-forward, which means a merge-commit (or merge bubble) is
created when merging. But sometimes you would rather want to merge without creating a merge bubble.

To merge a pull-request as fast-forward (no merge-commit) use the <comment>--fast-forward</comment>
option. Note that no merge-message is available and the changes are merged as if they were created in
the target branch directly!

    <info>$ gush %command.name% --fast-forward 12</info>

If you want to perform an automatic rebase against the base branch before merging, the <comment>--rebase</comment> option can be used
in order to try that operation:

    <info>$ gush %command.name% --rebase 12</info>

A synchronization check against the base branch can be done before the merge, passing the <comment>--ensure-sync</comment> option; so
if this check fails, the operation will be aborted:

    <info>$ gush %command.name% --ensure-sync 12</info>

After the pull request is merged, you can give a pat on the back to its author using the <comment>--pat</comment>.
This option accepts the name of any configured pat's name:

    <info>$ gush %command.name% --pat=thank_you 12</info>

If you omit it, you'll be prompted to choose one (default is <comment>none</comment>), but you can also choose to
not be prompted using <comment>--pat=none</comment>.
Additionally you can let gush use a random pat with <comment>--pat=random</comment>.

    <info>$ gush %command.name% --pat=random 12</info>

This option can be configured from your local <comment>.gush.yml</comment> file within this directive:
<comment>
pat_on_merge: thank_you # or null, none, random, etc.
</comment>

When this directive is configured, the configured pat will be used at least you use this <comment>--pat</comment> option,
which has precedence to the predefined configuration.

<comment>The whole pat configuration will be ignored and no pat will be placed if the pull request is authored by yourself!</comment>
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

        $this->guardPullRequestMerge($pr);

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        /** @var GitConfigHelper $gitConfigHelper */
        $gitConfigHelper = $this->getHelper('git_config');
        $styleHelper = $this->getHelper('gush_style');

        $sourceRemote = $pr['head']['user'];
        $sourceRepository = $pr['head']['repo'];
        $sourceBranch = $pr['head']['ref'];

        $targetRemote = $pr['base']['user'];
        $targetRepository = $pr['base']['repo'];
        $targetBranch = $pr['base']['ref'];

        $gitConfigHelper->ensureRemoteExists($targetRemote, $targetRepository);
        $gitConfigHelper->ensureRemoteExists($sourceRemote, $sourceRepository);

        if ($input->getOption('switch')) {
            $targetLabel = sprintf('New-target: %s/%s (was "%s")', $targetRemote, $input->getOption('switch'), $targetBranch);
        } else {
            $targetLabel = sprintf('Target: %s/%s', $targetRemote, $targetBranch);
        }

        $styleHelper->title(sprintf('Merging pull-request #%d - %s', $prNumber, $pr['title']));
        $styleHelper->text([sprintf('Source: %s/%s', $sourceRemote, $sourceBranch), $targetLabel]);

        if ($squash) {
            $styleHelper->note('This pull-request will be squashed before merging.');
        }

        $styleHelper->writeln('');

        try {
            $prType = $this->getPrType($prType, $input);
            $mergeNote = $this->getMergeNote($pr, $squash, $input->getOption('switch'));
            $commits = $adapter->getPullRequestCommits($prNumber);
            $messageCallback = function ($base, $tempBranch) use ($prType, $pr, $mergeNote, $gitHelper, $commits) {
                return $this->render(
                    'merge',
                    [
                        'type' => $prType,
                        'authors' => $this->getPrAuthors($commits, $pr['user']),
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
            $mergeOperation->guardSync($input->getOption('ensure-sync'));
            $mergeOperation->rebase($input->getOption('rebase'));
            $mergeOperation->switchBase($input->getOption('switch'));
            $mergeOperation->setMergeMessage($messageCallback);
            $mergeOperation->useFastForward($input->getOption('fast-forward'));

            $mergeCommit = $mergeOperation->performMerge();
            $mergeOperation->pushToRemote();

            if (!$input->getOption('no-comments') && !$input->getOption('fast-forward')) {
                $gitConfigHelper->ensureNotesFetching($targetRemote);

                $this->addCommentsToMergeCommit(
                    $adapter->getComments($prNumber),
                    $mergeCommit,
                    $targetRemote
                );
            }

            // Only close the PR explicitly when commit hashes have changed
            // This prevents getting an 'ugly' closed when there was an actual merge
            if ($squash || $input->getOption('switch')) {
                $adapter->closePullRequest($prNumber);
                $this->addClosedPullRequestNote($pr, $mergeCommit, $squash, $input->getOption('switch'));
            }

            if ($pr['user'] !== $this->getParameter($input, 'authentication')['username']) {
                $patComment = $this->givePatToPullRequestAuthor($pr, $input->getOption('pat'));
                if ($patComment) {
                    $styleHelper->note(sprintf('Pat given to @%s at %s.', $pr['user'], $patComment));
                }
            }

            $styleHelper->success([$mergeNote, $pr['url']]);

            return self::COMMAND_SUCCESS;
        } catch (CannotSquashMultipleAuthors $e) {
            $styleHelper->error([
                'Unable to squash commits when there are multiple authors.',
                'Use --force-squash to continue or ask the author to squash commits manually.',
            ]);

            $gitHelper->restoreStashedBranch();

            return self::COMMAND_FAILURE;
        }
    }

    private function guardPullRequestMerge(array $pr)
    {
        if ('open' !== $pr['state']) {
            throw new UserException(
                sprintf(
                    'Pull request #%s is already merged/closed, current status: %s',
                    $pr['number'],
                    $pr['state']
                ),
                self::COMMAND_FAILURE
            );
        }
    }

    private function addCommentsToMergeCommit(array $comments, $sha, $remote)
    {
        if (0 === count($comments)) {
            return;
        }

        $commentText = '';

        foreach ($comments as $comment) {
            $commentText .= $this->render('comment', [
                'login' => $comment['user'],
                'created_at' => $comment['created_at']->format('Y-m-d H:i'),
                'body' => $comment['body'],
            ]);
        }

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
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

    private function getPrAuthors(array $commits, $authorFallback = 'unknown')
    {
        if (!$commits) {
            return $authorFallback;
        }

        $authors = [];

        foreach ($commits as $commit) {
            $authors[] = $commit['user'];
        }

        return implode(', ', array_unique($authors, SORT_STRING));
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

    private function getPrType($prType, InputInterface $input)
    {
        $config = $this->getConfig();
        $types = $config->get('pr_type');

        if (null === $prType) {
            if (!$input->isInteractive()) {
                $prType = 'merge';
            } elseif (null !== $types) {
                $prType = $this->getHelper('gush_style')->choice('Type of the pull request', $types);
            } else {
                $prType = $this->getHelper('gush_style')->ask(
                    'Type of the pull request',
                    'merge',
                    function ($value) {
                        $value = trim($value);

                        if (false !== strpos($value, ' ')) {
                            throw new \InvalidArgumentException('Value cannot contain spaces.');
                        }

                        return $value;
                    }
                );
            }
        }

        if (null !== $types && !in_array($prType, $types, true)) {
            throw new UserException(
                sprintf(
                    "Pull-request type '%s' is not accepted, choose of one of: %s.",
                    $prType,
                    implode(', ', $types)
                ),
                self::COMMAND_FAILURE
            );
        }

        return $prType;
    }

    private function addClosedPullRequestNote(array $pr, $mergeCommit, $squash = false, $newBase = null)
    {
        $template = 'merge_note_';
        $params = [
            'originalBaseBranch' => $pr['base']['ref'],
            'mergeCommit' => $mergeCommit
        ];
        if ($squash && $newBase) {
            $template .= 'switched_base_and_squashed';
            $params['targetBaseBranch'] = $newBase;
        } elseif ($squash) {
            $template .= 'squashed';
        } elseif ($newBase) {
            $template .= 'switched_base';
            $params['targetBaseBranch'] = $newBase;
        } else {
            throw new \InvalidArgumentException('At least one of arguments 3 or 4 must evaluate to `true`');
        }

        $template .= '_and_closed';

        $this->getAdapter()->createComment($pr['number'], $this->render($template, $params));
    }

    private function givePatToPullRequestAuthor(array $pr, $pat)
    {
        $config = $this->getConfig();
        $configuredPat = $config->get('pat_on_merge');
        if (in_array('none', [$pat, $configuredPat], true)) {
            return;
        }

        if ($pats = $this->getConfig()->get('pats')) {
            Pats::addPats($pats);
        }

        if ($pat) {
            if ('random' === $pat) {
                $pat = Pats::getRandomPatName();
            }
        } elseif ($configuredPat) {
            $pat = $configuredPat;
            if ('random' === $pat) {
                $pat = Pats::getRandomPatName();
            }
        } else {
            $pats = ['none' => '(!) This option wil omit the pat to the PR\'s author'] + Pats::getPats();
            $pat = $this->getHelper('gush_style')->choice('Please, choose a pat ', $pats, reset($pats));
        }

        if ('none' !== $pat) {
            $patMessage = $this
                ->getHelper('template')
                ->bindAndRender(['pat' => $pat, 'author' => $pr['user']], 'pats', 'general')
            ;

            return $this->getAdapter()->createComment($pr['number'], $patMessage);
        }
    }
}
