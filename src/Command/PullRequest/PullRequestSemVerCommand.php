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
use Gush\Feature\GitFolderFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Herrera\Version\Dumper;
use Herrera\Version\Parser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestSemVerCommand extends BaseCommand implements GitRepoFeature, GitFolderFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:semver')
            ->setDescription('Provides information about the semver version of a pull-request')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull Request number')
            ->addOption('major', null, InputOption::VALUE_NONE, 'Conveys it is a major feature')
            ->addOption('minor', null, InputOption::VALUE_NONE, 'Conveys it is a minor feature')
            ->addOption('patch', null, InputOption::VALUE_NONE, 'Conveys it is a patch')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command provides information about semver version of a pull request:

    <info>$ gush %command.name% 12</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pr = $this->getAdapter()->getPullRequest($input->getArgument('pr_number'));

        $sourceOrg = $pr['head']['user'];
        $branchName = $pr['head']['ref'];

        /** @var GitConfigHelper $gitConfigHelper */
        $gitConfigHelper = $this->getHelper('git_config');
        $gitConfigHelper->ensureRemoteExists($sourceOrg, $pr['head']['repo']);

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $gitHelper->remoteUpdate($sourceOrg);

        $lastTag = $gitHelper->getLastTagOnBranch($sourceOrg.'/'.$branchName);

        if (empty($lastTag)) {
            $lastTag = '0.0.0';
        }

        // adjust case for format v2.3
        $lastTag = ltrim($lastTag, 'v');
        $builder = Parser::toBuilder($lastTag);

        switch (true) {
            case $input->getOption('major'):
                $builder->incrementMajor();
                break;

            case $input->getOption('minor'):
                $builder->incrementMinor();
                break;

            case $input->getOption('patch'):
                $builder->incrementPatch();
                break;

            default:
                $builder->incrementPatch();
                break;
        }

        $output->writeln(Dumper::toString($builder->getVersion()));

        return self::COMMAND_SUCCESS;
    }
}
