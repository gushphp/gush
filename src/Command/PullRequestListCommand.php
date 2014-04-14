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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;
use Gush\Feature\TableFeature;

/**
 * Lists all pull requests
 *
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class PullRequestListCommand extends BaseCommand  implements TableFeature, GitHubFeature
{
    /**
     * {@inheritDoc}
     */
    public function getTableDefaultLayout()
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:list')
            ->setDescription('Lists all available pull requests')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists all the pull requests:

    <info>$ gush %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter      = $this->getAdapter();
        $pullRequests = $adapter->getPullRequests();

        $table = $this->getHelper('table');
        $table->setHeaders(['ID', 'Title', 'State', 'Created', 'User']);
        $table->formatRows($pullRequests, $this->getRowBuilderCallback());
        $table->setFooter(sprintf('%s pull requests(s)', count($pullRequests)));
        $table->render($output, $table);

        return self::COMMAND_SUCCESS;
    }

    /**
     * @return callable
     */
    private function getRowBuilderCallback()
    {
        return function ($release) {
            return [
                $release['number'],
                $release['title'],
                ucfirst($release['state']),
                $release['created_at'],
                $release['head']['user']['login'],
            ];
        };
    }
}
