<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;

/**
 * Gives a pat on the back
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestPatOnTheBackCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:pat-on-the-back')
            ->setDescription('Gives a pat on the back to a PR\'s author')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull request number')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command gives a pat on the back to a PR's author with a random template:

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

        $patMessage = $this
            ->getHelper('template')
            ->bindAndRender(
                ['author' => $pr['user']],
                'pats',
                'general'
            )
        ;

        $adapter->createComment($prNumber, $patMessage);

        $url = $adapter->getPullRequestUrl($prNumber);
        $output->writeln("Pat on the back pushed to {$url}");

        return self::COMMAND_SUCCESS;
    }
}
