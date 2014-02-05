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
                'head',
                null,
                InputOption::VALUE_REQUIRED,
                'Head Branch - your branch name (defaults to current)'
            )
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'PR Title')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command is used to make a github pull request
against the configured organization and repository.

    <info>$ %command.full_name%</info>

The remote branch to make the PR against can be specified with the
<info>base</info> option, and the local branch with the <info>head</info>
option, when these options are omitted they are determined from the current
context.

    <info>$ %command.full_name% --head=my_branch --base=dev</info>

A pull request template can be specified with the <info>template</info> option:

    <info>$ %command.full_name% --template=symfony</info>

This will use the symfony specific pull request template, the full list of
available templates is displayed in the description of the <info>template</info>
option.

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
        $repo = $input->getOption('repo');

        $base = $input->getOption('base');
        $head = $input->getOption('head');

        $template = $input->getOption('template');

        if (null === $head) {
            $head = $this->getHelper('git')->getBranchName();
        }

        $github = $this->getParameter('github');
        $username = $github['username'];

        if (!$title = $input->getOption('title')) {
            $title = $this->getHelper('dialog')->ask($output, 'Title: ');
        }

        $body = $this->getHelper('template')->askAndRender($output, $this->getTemplateDomain(), $template);

        if (true === $input->getOption('verbose')) {
            $output->writeln(sprintf(
                'Making PR from <info>%s:%s</info> to <info>%s:%s</info>',
                $username,
                $head,
                $org,
                $base
            ));
        }

        $pullRequest = $this
            ->getGithubClient()
            ->api('pull_request')
            ->create(
                $org,
                $repo,
                [
                    'base'  => $org.':'.$base,
                    'head'  => $username.':'.$head,
                    'title' => $title,
                    'body'  => $body,
                ]
            )
        ;

        $output->writeln($pullRequest['html_url']);

        return self::COMMAND_SUCCESS;
    }
}
