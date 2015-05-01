<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Exception\UserException;
use Gush\Feature\GitRepoFeature;
use Gush\Feature\TemplateFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestCreateCommand extends BaseCommand implements GitRepoFeature, TemplateFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:create')
            ->setDescription('Launches a pull request')
            ->addOption('base', null, InputOption::VALUE_REQUIRED, 'Base Branch - remote branch name')
            ->addOption(
                'source-org',
                null,
                InputOption::VALUE_REQUIRED,
                'Source Organization - source organization name (defaults to current)'
            )
            ->addOption(
                'source-repo',
                null,
                InputOption::VALUE_REQUIRED,
                'Source Repository - source Repository name (defaults to current)'
            )
            ->addOption(
                'source-branch',
                null,
                InputOption::VALUE_REQUIRED,
                'Source Branch - source branch name (defaults to current)'
            )
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'PR Title')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command is used to make a pull-request
against the configured organization and repository.

    <info>$ gush %command.name%</info>

The remote branch to make the PR against can be specified with the
<comment>--base</comment> option, and the local organization / branch with the <comment>--source-org</comment> /
<comment>--source-branch</comment> options, when these options are omitted they are determined from the current
context.

    <info>$ gush %command.name% --source-branch=my_branch --source-org=my_org --base=dev</info>

A pull-request template can be specified with the <info>template</info> option:

    <info>$ gush %command.name% --template=symfony</info>

This will use the Symfony specific pull-request template, the full list of
available templates is displayed in the description of the <info>template</info>
option.

Note: The "custom" template is only supported when you have configured this in
your local <comment>.gush.yml</comment> file like:
<comment>
table-pr:
    bug_fix: ['Bug Fix?', no]
    new_feature: ['New Feature?', no]
    bc_breaks: ['BC Breaks?', no]
    deprecations: ['Deprecations?', no]
    tests_pass: ['Tests Pass?', no]
    fixed_tickets: ['Fixed Tickets', '']
    license: ['License', MIT]
</comment>

Each key in "table-pr" list is the name used internally by the command engine, you can choose any name
you like but note that "description" is preserved for internal usage and is not changeable
and you can only use underscores for separating words.

The value of each key is an array with "exactly two values" like ['the label', 'the default value'].

If you don't want to configure any fields at all use the following.
<comment>
table-pr: []
</comment>
<info>This will still ask the title and description, but no additional fields.</info>

When using a template you will be prompted to fill out the required parameters.

EOF
            )
        ;
    }

    public function getTemplateDomain()
    {
        return 'pull-request-create';
    }

    public function getTemplateDefault()
    {
        return 'symfony';
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $template = $input->getOption('template');

        $sourceOrg = $input->getOption('source-org');
        $sourceRepo = $input->getOption('source-repo') ?: $input->getOption('repo') ;
        $sourceBranch = $input->getOption('source-branch');
        $base = $input->getOption('base');

        $config = $this->getConfig();

        if (null === $base) {
            $base = $config->get('base') ?: 'master';
        }

        if (null === $sourceOrg) {
            $sourceOrg = $this->getParameter($input, 'authentication')['username'];
        }

        if (null === $sourceBranch) {
            $sourceBranch = $this->getHelper('git')->getActiveBranchName();
        }

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        $styleHelper->title(sprintf('Open request on %s/%s', $org, $input->getOption('repo')));
        $styleHelper->text(
            [
                sprintf('This pull-request will be opened on "%s/%s".', $org, $input->getOption('repo')),
                sprintf('The source branch is "%s" on "%s".', $sourceBranch, $sourceOrg),
            ]
        );
        $styleHelper->newLine();

        $this->guardRemoteBranchExist($sourceOrg, $sourceRepo, $sourceBranch, $input, $styleHelper);

        $defaultTitle = $input->getOption('title') ?: $this->getHelper('git')->getFirstCommitTitle($base, $sourceBranch);

        if ('' === $defaultTitle && !$input->isInteractive()) {
            $styleHelper->error(
                'Title cannot be empty, use the "--title" option to provide a title in none-interactive mode.'
            );

            return self::COMMAND_FAILURE;
        }

        $title = trim($styleHelper->ask('Title', $defaultTitle));
        $body = trim(
            $this->getHelper('template')->askAndRender(
                $this->getTemplateDomain(),
                $template
            )
        );

        if (true === $config->get('remove-promote')) {
            $body = $this->appendPlug($body);
        }

        $parameters = [];
        $pullRequest = $this
            ->getAdapter()
            ->openPullRequest(
                $base,
                $sourceOrg.':'.$sourceBranch,
                $title,
                $body,
                $parameters
            )
        ;

        $this->getHelper('gush_style')->success("Opened pull request {$pullRequest['html_url']}");

        return self::COMMAND_SUCCESS;
    }

    private function guardRemoteBranchExist($org, $repo, $branch, InputInterface $input, StyleHelper $styleHelper)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        /** @var GitConfigHelper $gitConfigHelper */
        $gitConfigHelper = $this->getHelper('git_config');

        $gitUrl = $this->getAdapter()->getRepositoryInfo($org, $repo)['push_url'];

        if ($gitHelper->remoteBranchExists($gitUrl, $branch)) {
            return; // branch exists, continue
        }

        if ($gitHelper->branchExists($branch)) {
            $gitConfigHelper->ensureRemoteExists($org, $repo);

            // This ensures the connection is possible, but does not ensure access is granted.
            $gitHelper->remoteUpdate($org);

            if (!$input->isInteractive()) {
                $gitHelper->pushToRemote($org, $branch, true);

                $styleHelper->success(sprintf('Branch "%s" was pushed to "%s".', $branch, $org));

                return ;
            } else {
                $styleHelper->note(
                    [
                        sprintf(
                            'Branch "%s" does not exist in "%s/%s", but it does exist locally.',
                            $branch,
                            $org,
                            $repo
                        ),
                        'You can push the branch now, but make sure you have push access and that your SSH agent is active.'
                    ]
                );

                if ($styleHelper->confirm('Do you want to push the branch now?', true)) {
                    $gitHelper->pushToRemote($org, $branch, true);

                    $styleHelper->success(sprintf('Branch "%s" was pushed to "%s".', $branch, $org));

                    return;
                }
            }
        }

        throw new UserException(
            sprintf('Cannot open pull-request, remote branch "%s" does not exist in "%s/%s".', $branch, $org, $repo)
        );
    }
}
