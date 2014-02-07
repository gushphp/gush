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

use Gush\Template\Pats;
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
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');
        $client = $this->getGithubClient();

        $prNumber = $input->getArgument('pr_number');

        $pr = $client->api('pull_request')->show($org, $repo, $prNumber);

        $patMessage = $this
            ->getHelper('template')
            ->bindAndRender(
                ['author' => $pr['user']['login']],
                'pats/general',
                'pats'
            )
        ;

        $client
            ->api('issue')
            ->comments()
            ->create($org, $repo, $prNumber, ['body' => $patMessage])
        ;

        $output->writeln("Pat on the back pushed to https://github.com/{$org}/{$repo}/pull/{$prNumber}");

        return self::COMMAND_SUCCESS;
    }
}
