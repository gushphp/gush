<?php

/*
 * This file is part of the Gush.
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

/**
 * Lists the milestones for the issues
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class IssueMilestoneListCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:list:milestones')
            ->setDescription('List of the issue\'s milestones')
            ->addArgument('org', InputArgument::OPTIONAL, 'Name of the GitHub organization', $this->getVendorName())
            ->addArgument('repo', InputArgument::OPTIONAL, 'Name of the GitHub repository', $this->getRepoName())
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $organization = $input->getArgument('org');
        $repository = $input->getArgument('repo');

        $client = $this->getGithubClient();
        $milestones = $client->api('issue')->milestones()->all($organization, $repository);

        $tabulator = $this->getTabulator();
        $tabulator->tabulate(
            $table = $tabulator->createTable(),
            $milestones,
            $this->getRowBuilderCallback()
        );
        $tabulator->render($output, $table);

        return $milestones;
    }

    private function getRowBuilderCallback()
    {
        return function($milestone) {
            return [$milestone['title']];
        };
    }
}
