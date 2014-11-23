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
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitHelper;
use Herrera\Version\Dumper;
use Herrera\Version\Parser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestSemVerCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:semver')
            ->setDescription('Provides information about the semver version of a pull request')
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
        $prNumber = $input->getArgument('pr_number');
        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);
        $branchName = $pr['head']['ref'];

        $gitHelper = $this->getHelper('git');
        /** @var GitHelper $gitHelper */

        $gitHelper->remoteUpdate();

        $lastTag = $gitHelper->getLastTagOnBranch('origin/'.$branchName);

        if (empty($lastTag)) {
            $lastTag = '0.0.0';
        }

        // adjust case for format v2.3
        if ($lastTag[0] === 'v') {
            $lastTag = ltrim($lastTag, 'v');
        }

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
