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
        $this
            ->setName('release:list')
            ->setDescription('List of the releases')
            ->addArgument('org', InputArgument::OPTIONAL, 'Name of the GitHub organization', $this->getVendorName())
            ->addArgument('repo', InputArgument::OPTIONAL, 'Name of the GitHub repository', $this->getRepoName())
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

        $tabulator = $this->getTabulator();
        $tabulator->tabulate(
            $table = $tabulator->createTable(),
            $releases,
            $this->getRowBuilderCallback()
        );
        $table->setHeaders(array('ID', 'Name', 'Tag', 'Commitish', 'Draft', 'Prerelease', 'Created', 'Published'));
        $tabulator->render($output, $table);
    }

    private function getRowBuilderCallback()
    {
        return function ($release) {
            return [
                $release['id'],
                $release['name'] ? : 'not set',
                $release['tag_name'],
                $release['target_commitish'],
                $release['draft'] ? 'yes': 'no',
                $release['prerelease'] ? 'yes' : 'no',
                $release['created_at'],
                $release['published_at'],
            ];
        };
    }
}
