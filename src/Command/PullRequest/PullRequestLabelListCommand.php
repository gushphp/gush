<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
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

class PullRequestLabelListCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:label:list')
            ->setDescription('Lists the available labels for pull-requests')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists the available labels for pull-requests for either the current
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
        $labels = $adapter->getLabels();

        $this->getHelper('gush_style')->listing($labels);

        return self::COMMAND_SUCCESS;
    }
}
