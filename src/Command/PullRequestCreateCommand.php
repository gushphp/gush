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
use Symfony\Component\Console\Input\InputOption;
use Gush\Feature\TemplateFeature;

/**
 * Launches a pull request
 *
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Daniel Leech <daniel@dantleech.com>
 */
class PullRequestCreateCommand extends BaseCommand implements GitHubFeature, TemplateFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:create')
            ->setDescription('Launches a pull request')
            ->addOption('base', null, InputOption::VALUE_REQUIRED, 'Base Branch - remote branch name', 'master')
            ->addOption(
                'source-org',
                null,
                InputOption::VALUE_REQUIRED,
                'Source Organization - source organization name (defaults to current)'
            )
            ->addOption(
                'source-branch',
                null,
                InputOption::VALUE_REQUIRED,
                'Source Branch - source branch name (defaults to current)'
            )
            ->addOption('issue', null, InputOption::VALUE_REQUIRED, 'Issue Number')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'PR Title')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command is used to make a github pull request
against the configured organization and repository.

    <info>$ %command.full_name%</info>

The remote branch to make the PR against can be specified with the
<info>base</info> option, and the local organization / branch with the <info>source-org</info> /
<info>source-branch</info> options, when these options are omitted they are determined from the current
context.

    <info>$ %command.full_name% --source-branch=my_branch --source-org=my_org --base=dev</info>

A pull request template can be specified with the <info>template</info> option:

    <info>$ %command.full_name% --template=symfony</info>

This will use the symfony specific pull request template, the full list of
available templates is displayed in the description of the <info>template</info>
option.

The command also can accept an issue number along with the other options:

    <info>$ %command.full_name% --issue=10430</info>

Passing an issue number would turn the issue into a pull request provided permissions
allow it.

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
        $base = $input->getOption('base');
        $issueNumber = $input->getOption('issue');
        $sourceOrg = $input->getOption('source-org');
        $sourceBranch = $input->getOption('source-branch');
        $template = $input->getOption('template');

        if (null === $sourceOrg) {
            $sourceOrg = $this->getHelper('git')->getVendorName();
        }

        if (null === $sourceBranch) {
            $sourceBranch = $this->getHelper('git')->getBranchName();
        }

        if (!$title = $input->getOption('title')) {
            $title = $this->getHelper('dialog')->ask($output, 'Title: ');
        }

        $body = $this->getHelper('template')->askAndRender($output, $this->getTemplateDomain(), $template);

        if (true === $input->getOption('verbose')) {
            $message = sprintf(
                'Making PR from <info>%s:%s</info> to <info>%s:%s</info>',
                $sourceOrg,
                $sourceBranch,
                $org,
                $base
            );

            if (null !== $issueNumber) {
                $message = $message.' for issue #'.$issueNumber;
            }

            $output->writeln($message);
        }
        
        $parameters = $issueNumber ? ['issue' => $issueNumber]: [];

        $pullRequest = $this
            ->getAdapter()
            ->openPullRequest(
                $org.':'.$base,
                $sourceOrg.':'.$sourceBranch,
                $title,
                $body,
                $parameters
            );

        $output->writeln($pullRequest['html_url']);

        return self::COMMAND_SUCCESS;
    }
}
