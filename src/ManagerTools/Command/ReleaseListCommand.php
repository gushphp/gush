<?php

/*
 * This file is part of the Manager Tools.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ManagerTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class ReleaseListCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('release:list')
            ->setDescription('List of the releases')
            ->addArgument('org', InputArgument::REQUIRED, 'Name of the GitHub organization')
            ->addArgument('repo', InputArgument::REQUIRED, 'Name of the GitHub repository')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getGithubClient();
        $repo = $input->getArgument('repo');
        $org = $input->getArgument('org');

        $releases = $client->api('repo')->releases()->all($org, $repo);

        $table = $this->getHelper('table');
        $table->setHeaders(array('ID', 'Name', 'Tag', 'Commitish', 'Draft', 'Prerelease', 'Created', 'Published'));

        foreach ($releases as $release) {
            $table->addRow(array(
                $release['id'],
                $release['name'] ? : 'not set',
                $release['tag_name'],
                $release['target_commitish'],
                $release['draft'] ? 'yes': 'no',
                $release['prerelease'] ? 'yes' : 'no',
                $release['created_at'],
                $release['published_at'],
            ));
        }

        $table->render($output);
    }
}


