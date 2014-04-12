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

use Herrera\Version\Dumper;
use Herrera\Version\Parser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;

/**
 * Provides SemVer information for a PR
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestSemVerCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:semver')
            ->setAliases(array('pr:semver'))
            ->setDescription('Provides information about the semver version of a pull request')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull Request number')
            ->addOption('major', null, InputOption::VALUE_NONE, 'Conveys it is a major feature')
            ->addOption('minor', null, InputOption::VALUE_NONE, 'Conveys it is a minor feature')
            ->addOption('patch', null, InputOption::VALUE_NONE, 'Conveys it is a patch')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command provides information about semver version of a pull request:

    <info>$ gush %command.full_name% 12</info>
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

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => 'git remote update',
                    'allow_failures' => true,
                ]
            ]
        );

        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);
        $branchToCheckout = $pr['head']['ref'];

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => sprintf('git checkout -b %s origin/%s', $branchToCheckout, $branchToCheckout),
                    'allow_failures' => true,
                ],
                [
                    'line' => sprintf('git checkout %s', $branchToCheckout),
                    'allow_failures' => true,
                ],
            ]
        );

        $lastTag = $this->getHelper('git')->getLastTagOnCurrentBranch();

        if (empty($lastTag)) {
            $lastTag  = "0.0.0";
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
