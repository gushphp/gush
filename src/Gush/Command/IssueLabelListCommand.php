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

/**
 * Lists the labels for the issues
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class IssueLabelListCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:label:list')
            ->setDescription('List of the issue\'s labels')
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

        $labels = $client->api('issue')->labels()->all($organization, $repository);

        $rowCallback = function ($label) { return array($label['name']); };
        $tabulator = $this->getTabulator();
        $tabulator->tabulate($table = $tabulator->createTable(), $labels, $rowCallback);
        $tabulator->render($output, $table);

        return $labels;
    }
}
