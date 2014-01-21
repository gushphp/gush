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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;

/**
 * Pad in the back
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PadInTheBackCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:close')
            ->setDescription('Closes an issue')
            ->addArgument('issue_number', InputArgument::REQUIRED, 'Issue number to be closed')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Closing comment')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command closes an issue for either the current or the given organization
and repository:

    <info>$ php %command.full_name% 12 -m"let's try to keep it low profile guys."</info>
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

        $issueNumber = $input->getArgument('issue_number');
        $closingComment = $input->getOption('message');

        $client = $this->getGithubClient();

        $parameters = ['state' => 'closed'];
        $client->api('issue')->update($org, $repo, $issueNumber, $parameters);

        if ($input->getOption('message')) {
            $parameters = ['body' => $closingComment];
            $client->api('issue')->comments()->create($org, $repo, $issueNumber, $parameters);
        }

        $output->writeln("Closed https://github.com/{$org}/{$repo}/issues/{$issueNumber}");

        return self::COMMAND_SUCCESS;
    }
}
