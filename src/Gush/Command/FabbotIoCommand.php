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

use Gush\Adapter\GitHubAdapter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Gush\Feature\GitHubFeature;

/**
 * Applies patches from fabbot-io robot
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class FabbotIoCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:fabbot-io')
            ->setDescription('Run fabbot-io patches on given PR')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command applies patch fabbot-io robot on given PR:

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
        if (!$this->getAdapter() instanceof GitHubAdapter) {
            throw new \Exception("To use fabbot.io, you must be using the github adapter");
        }

        $org = $input->getOption('org');
        $repo = $input->getOption('repo');

        $prNumber = $input->getArgument('pr_number');

        $github = $this->getParameter('authentication');
        $username = $github['username'];

        $adapter = $this->getAdapter();
        $pr      = $adapter->getPullRequest($prNumber);

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => 'git checkout '.$pr['head']['ref'],
                    'allow_failures' => true
                ]
            ],
            $output
        );

        $commandLine = sprintf(
            'curl http://fabbot.io/patch/%s/%s/%s/%s/cs.diff | patch -p0',
            $org,
            $repo,
            $prNumber,
            $pr['head']['sha']
        );

        $process = new Process($commandLine, getcwd());
        $process->run();

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => sprintf('git push -u %s %s -f', $username, $pr['head']['ref']),
                    'allow_failures' => true
                ]
            ],
            $output
        );

        return self::COMMAND_SUCCESS;
    }
}
