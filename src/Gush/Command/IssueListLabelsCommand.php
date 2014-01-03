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

use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists the labels for the issues
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class IssueListLabelsCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issues:list:labels')
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

        /** @var TableHelper $table */
        $table = $this->getApplication()->getHelperSet()->get('table');
        $table->setLayout(TableHelper::LAYOUT_BORDERLESS);
        $table->setHorizontalBorderChar('');
        $table->setPaddingChar(' ');
        $table->setVerticalBorderChar('');

        foreach ($labels as $label) {
            $table->addRow(array($label['name']));
        }

        $table->render($output);

        return $labels;
    }
}
