<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Applies patches from fabbot-io robot
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class FabbotIoCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:fabbot-io')
            ->setDescription('Run fabbot-io patches')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $this->getVendorName();
        $repo = $this->getRepoName();
        $prNumber = $input->getArgument('pr_number');

        $github = $this->getParameter('github');
        $username = $github['username'];

        $client = $this->getGithubClient();
        $pr = $client->api('pull_request')->show($org, $repo, $prNumber);

        $this->runCommands([
                [
                    'line' => 'git checkout '.$pr['head']['ref'],
                    'allow_failures' => true
                ]
            ]
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

        $this->runCommands([
                [
                    'line' => sprintf('git push -u %s %s -f', $username, $pr['head']['ref']),
                    'allow_failures' => true
                ]
            ]
        );
    }
}
