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
 * List releases
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ReleaseListCommand extends BaseCommand implements TableFeature, GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('release:list')
            ->setDescription('Lists the releases')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists the available releases:

    <info>$ gush %command.full_name%</info>
EOF
            )
        ;
    }

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = $this->getAdapter();
        $releases = $adapter->getReleases();

        $table = $this->getHelper('table');
        $table->setHeaders(['ID', 'Name', 'Tag', 'Commitish', 'Draft', 'Prerelease', 'Created', 'Published']);
        $table->formatRows($releases, $this->getRowBuilderCallback());
        $table->setFooter(sprintf('%s release(s)', count($releases)));
        $table->render($output, $table);

        return self::COMMAND_SUCCESS;
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
