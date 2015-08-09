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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BranchRemoteAddCommand extends BaseCommand implements GitRepoFeature, GitFolderFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:remote:add')
            ->setDescription('Adds a remote with url used from adapter')
            ->addArgument(
                'other_organization',
                InputArgument::OPTIONAL,
                'Organization or username the remote will point to'
            )
            ->addArgument(
                'other_repository',
                InputArgument::OPTIONAL,
                'Repository-name the remote will point to'
            )
            ->addArgument(
                'remote',
                InputArgument::OPTIONAL,
                'Remote name. When not provided the other_organization is used as remote-name'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command adds a remote with a url provided by the adapter:

    <info>$ gush %command.name% sstok gush</info>

<fg=yellow;options=bold>Warning! Any existing remote with the same name will be overwritten!</>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getArgument('other_organization') ?: $this->getParameter($input, 'authentication')['username'];
        $repo = $input->getArgument('other_repository') ?: $input->getOption('repo');
        $remoteName = $input->getArgument('remote') ?: $org;

        $repoInfo = $this->getAdapter()->getRepositoryInfo($org, $repo);

        $this->getHelper('git_config')->setRemote($remoteName, $repoInfo['push_url']);
        $this->getHelper('gush_style')->success(sprintf('Added remote "%s" with "%s"', $remoteName, $repoInfo['push_url']));

        return self::COMMAND_SUCCESS;
    }
}
