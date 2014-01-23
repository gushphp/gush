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
            ->setDescription('List of the issue\'s milestones')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');

        $client = $this->getGithubClient();
        $milestones = $client->api('issue')->milestones()->all($org, $repo);

        $table = $this->getHelper('table');
        $table->setLayout('compact');
        $table->formatRows($milestones, $this->getRowBuilderCallback());
        $table->render($output, $table);

        return $milestones;
    }

    private function getRowBuilderCallback()
    {
        return function ($milestone) {
            return [$milestone['title']];
        };
    }
}
