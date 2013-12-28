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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ReleaseListCommand extends Command
{
    protected $workDir;

    protected function configure()
    {
        $this->setName('release:list')
            ->setDescription('List releases')
            ;
        $this->addArgument('org', InputArgument::REQUIRED, 'Name of GITHub organization');
        $this->addArgument('repo', InputArgument::REQUIRED, 'Name of GITHub repository');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getApplication()->getGithubClient();
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


