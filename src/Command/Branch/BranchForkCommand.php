<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Branch;

use Gush\Command\BaseCommand;
use Gush\Feature\GitFolderFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BranchForkCommand extends BaseCommand implements GitRepoFeature, GitFolderFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:fork')
            ->setDescription('Forks current upstream repository')
            ->addArgument(
                'target_organization',
                InputArgument::OPTIONAL,
                'Target organization to create the fork in. (Defaults to your username)'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command forks the upstream (defined by --org and --repo) repository
and adds the remote to your local Git configuration:

    <info>$ gush %command.name%</info>

By default this will fork the upstream to your username-organization, to fork into a different
target-organization use the following instead (where my-other-org is the name of the organization
you want to fork to):

    <info>$ gush %command.name% my-other-org</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceOrg = $input->getOption('org');
        $repo = $input->getOption('repo');
        $targetOrg = $input->getArgument('target_organization');

        if (null === $targetOrg) {
            $targetOrg = $this->getParameter($input, 'authentication')['username'];
        }

        $fork = $this->getAdapter()->createFork($targetOrg);

        $this->getHelper('gush_style')->success(
            sprintf(
                'Forked repository %s/%s into %s/%s',
                $sourceOrg,
                $repo,
                $targetOrg,
                $repo
            )
        );

        /** @var GitConfigHelper $gitConfigHelper */
        $gitConfigHelper = $this->getHelper('git_config');
        $gitConfigHelper->setRemote($targetOrg, $fork['git_url']);

        $this->getHelper('gush_style')->success(
            sprintf('Added remote "%s" with "%s".', $targetOrg, $fork['git_url'])
        );

        return self::COMMAND_SUCCESS;
    }
}
