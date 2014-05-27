<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
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
 * Lists the milestones for the issues
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class IssueMilestoneListCommand extends BaseCommand implements TableFeature, GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:list:milestones')
            ->setDescription('Lists the issue\'s milestones')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists the issue's milestones for either the current or the given organization
and repository:

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritDoc}
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
        $adapter = $this->getIssueTracker();
        $milestones = $adapter->getMilestones();

        $table = $this->getHelper('table');
        $table->formatRows($milestones, $this->getRowBuilderCallback());
        $table->render($output, $table);

        return $milestones;
    }

    private function getRowBuilderCallback()
    {
        return function ($milestone) {
            return [$milestone];
        };
    }
}
