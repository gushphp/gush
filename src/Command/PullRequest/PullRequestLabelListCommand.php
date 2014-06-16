<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Gush\Feature\TableFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists the available labels for a pull-request.
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class PullRequestLabelListCommand extends BaseCommand implements TableFeature, GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:label:list')
            ->setDescription('Lists the pull-request\'s labels')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists the pull-request's available labels for either the current
or the given organization and repository:

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableDefaultLayout()
    {
        return 'compact';
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = $this->getAdapter();
        $labels = $adapter->getLabels();

        $table = $this->getHelper('table');
        $table->formatRows($labels, function ($label) {
            return [$label];
        });
        $table->render($output, $table);

        return $labels;
    }
}
