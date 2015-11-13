<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestMilestoneListCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:list:milestones')
            ->setDescription('Lists the pull-request\'s available milestones')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists the pull-request's available milestones for either the current
or the given organization and repository:

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = $this->getAdapter();
        $milestones = $adapter->getMilestones();

        $styleHelper = $this->getHelper('gush_style');
        $styleHelper->title(
            sprintf(
                'Pull request milestones on %s / %s',
                $input->getOption('org'), $input->getOption('repo')
            )
        );

        $styleHelper->listing($milestones);
    }
}
