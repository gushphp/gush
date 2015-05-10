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
use Gush\Config;
use Gush\Exception\CannotSquashMultipleAuthors;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Gush\Validator\MergeWorkflowValidator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BranchMergeCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:merge')
            ->setDescription('Merges the remote branch into the given branch')
            ->addArgument('source_branch', InputArgument::REQUIRED, 'Source branch to merge from')
            ->addArgument('target_branch', InputArgument::REQUIRED, 'Target branch to merge to')
            ->addOption(
                'message',
                null,
                InputOption::VALUE_OPTIONAL,
                'Optional message to use for the merge commit, default is: Merge branch \'{{source}}\' into {{target}}'
            )
            ->addOption(
                'no-log',
                null,
                InputOption::VALUE_NONE,
                'Do not append a commit summary log'
            )
            ->addOption(
                'fast-forward',
                'ff',
                InputOption::VALUE_NONE,
                'Merge branch using fast forward (no merge commit will be created)'
            )
            ->addOption(
                'squash',
                null,
                InputOption::VALUE_NONE,
                'Squash the commits before merging'
            )
            ->addOption(
                'force-squash',
                null,
                InputOption::VALUE_NONE,
                'Force squashing the commits, even if there are multiple authors (this will implicitly use --squash)'
            )
            ->addOption(
                'ignore-workflow',
                null,
                InputOption::VALUE_NONE,
                'Ignore merge workflow configuration'
            )
            ->addOption(
                'source-org',
                null,
                InputOption::VALUE_REQUIRED,
                'Source Organization - source organization name (defaults to current organization)'
            )
            ->addOption(
                'source-repo',
                null,
                InputOption::VALUE_REQUIRED,
                'Source Repository - source Repository name (defaults to current repository)'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command merges the given source branch into the target branch:

    <info>$ gush %command.name% 2.3 2.7</info>

The default merge message is <comment>Merge branch '{{ source }}' into {{ target }}\\n{{ commits_summary }}</>

But you can change this by using the <comment>--message</> option:

    <info>$ gush %command.name% 2.3 2.7 --message="Merge upstream changes into master"</info>

The message is appended with a commits summary, to disable this use the <comment>--no-log</> option.

    <info>$ gush %command.name% 2.3 2.7 --no-log --message="Merge upstream changes into master"</info>

Squashing commits
-----------------

If there are many unrelated commits (like cs fixes) you can squash all the commits of the source branch
into one big commit using:

    <info>$ gush %command.name% --squash 2.3 2.7</info>

This will use the message-body and author of the first commit in the source branch.

<comment>Note:</> Squashing the sources branch requires that all the commits in the source branch
were done by one author. You can overwrite this behaviour with <comment>--force-squash</>

Merge workflow
--------------

Note: The merge workflow it only applied for merges performed with the %command.name%,
pull-requests and using Git directly doesn't use the configured workflow.

To prevent merging a newer version into an older one, the %command.name% always
checks if the merge is correct according to your (teams) workflow.

The default workflow checks if the source branch and target branch are
versions, then checks if the source branch is lower then the target branch (merging
bug fixes into a new version). And allows to merge "develop" into "master" (but not the
other way around).

If you have a more complex workflow you can configure this in your local
<comment>.gush.yml</comment> file.

Gush already supports a number of standard workflows, but creating your own is also possible.

The merge_workflow.validation configuration is build is follow:

  * "preset": use a standardized workflow, e.g: "git-flow", "semver" or "none".
  * "branches": set a more specific validation (on top of the preset), each entry is a
    key (source branch) and the value an array of allowed target branches.
  * "unknown_branch_policy": what must be done when no rule matches, e.g: allow-merge, deny-merge.

<warning>To prevent casting the branch name to a normalized number, always use quotes for a numeric
branch name like '2.0'.</warning>

Note that "branches" are validated after "preset", to only use "branches" set "none" as the "preset" value.

Default workflow (allows to merge an older version into a newer one, but not the other way around,
and allows any other merge):
<comment>
merge_workflow:
    validation:
        preset: semver
        branches: []
</comment>

GitFlow as described in http://nvie.com/posts/a-successful-git-branching-model/:
<comment>
merge_workflow:
    validation:
        preset: git-flow
</comment>

Semantic version (allows to merge an older version into a newer once, but not the other way around):
<comment>
merge_workflow:
    validation:
        preset: semver
</comment>

Fully custom workflow validation:
<comment>
merge_workflow:
    validation:
        preset: none
        unknown_branch_policy: allow-merge
        branches:
            develop: [master]
            stable: [develop]
            '2.3': ['2.4']
            '2.4': ['2.5']
            '2.5': ['master']
</comment>

If you want to skip the workflow for the current merge use the <comment>--ignore-workflow</> option.

    <info>$ gush %command.name% --ignore-workflow 2.7 2.3</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        /** @var GitConfigHelper $gitConfigHelper */
        $gitConfigHelper = $this->getHelper('git_config');

        $targetOrg = $input->getOption('org');
        $targetRepo = $input->getOption('repo');

        $sourceOrg = $input->getOption('source-org');
        $sourceRepo = $input->getOption('source-repo');

        if (null === $sourceOrg) {
            $sourceOrg = $targetOrg;
        }

        if (null === $sourceRepo) {
            $sourceRepo = $targetRepo;
        }

        $gitConfigHelper->ensureRemoteExists($sourceOrg, $sourceRepo);
        $gitConfigHelper->ensureRemoteExists($targetOrg, $targetRepo);

        $squash = $input->getOption('squash') || $input->getOption('force-squash');

        $targetBranch = $input->getArgument('target_branch');
        $sourceBranch = $input->getArgument('source_branch');

        if ($input->getOption('fast-forward')) {
            $this->getHelper('gush_style')->note('Merging with fast-forward, merge message is not available.');
        }

        $this->validateWorkFlow($input, $sourceBranch, $targetBranch);

        $message = (string) $input->getOption('message');

        if ('' === $message) {
            $message = sprintf(
                "Merge branch '%s' into %s",
                ($sourceOrg !== $targetOrg ? $sourceOrg.'/' : '').$sourceBranch,
                $targetBranch
            );
        }

        try {
            $mergeOperation = $gitHelper->createRemoteMergeOperation();
            $mergeOperation->setSource($sourceOrg, $sourceBranch);
            $mergeOperation->setTarget($targetOrg, $targetBranch);
            $mergeOperation->squashCommits($squash, $input->getOption('force-squash'));
            $mergeOperation->setMergeMessage($message, !$input->getOption('no-log'));
            $mergeOperation->useFastForward($input->getOption('fast-forward'));

            $mergeOperation->performMerge();
            $mergeOperation->pushToRemote();

            $this->getHelper('gush_style')->success(
                sprintf(
                    'Branch "%s" has been merged into "%s".',
                    $sourceOrg.'/'.$sourceBranch,
                    $targetOrg.'/'.$targetBranch
                )
            );

            return self::COMMAND_SUCCESS;
        } catch (CannotSquashMultipleAuthors $e) {
            $this->getHelper('gush_style')->error(
                [
                    "Enable to squash commits when there are multiple authors.",
                    "Use --force-squash to continue or ask the author to squash commits manually."
                ]
            );

            $gitHelper->restoreStashedBranch();

            return self::COMMAND_FAILURE;
        }
    }

    private function validateWorkFlow(InputInterface $input, $source, $target)
    {
        if ($input->getOption('ignore-workflow')) {
            $this->getHelper('gush_style')->note('Ignoring merge-workflow.');

            return;
        }

        $config = array_merge(
            [
                'preset' => 'semver',
                'branches' => [],
                'unknown_branch_policy' => MergeWorkflowValidator::BRANCH_POLICY_ALLOW
            ],
            $this->getConfig()->get(['merge_workflow', 'validation'], Config::CONFIG_LOCAL, [])
        );

        $validator = new MergeWorkflowValidator(
            $config['preset'],
            $config['branches'],
            $config['unknown_branch_policy']
        );

        $validator->validate($source, $target);
    }
}
