<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Issue;

use Gush\Command\BaseCommand;
use Gush\Feature\IssueTrackerRepoFeature;
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IssueMilestoneListCommand extends BaseCommand implements IssueTrackerRepoFeature
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
The <info>%command.name%</info> command lists the issue's available milestones for either the current
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
        $styleHelper = $this->getHelper('gush_style');
        $styleHelper->title(sprintf('Issue milestones on %s/%s', $input->getOption('issue-org'), $input->getOption('issue-project')));
        $styleHelper->listing($this->getIssueTracker()->getMilestones());
    }
}
