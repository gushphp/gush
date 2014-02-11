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

class PullRequestVersionEyeCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:version-eye')
            ->setDescription('Update composer.json dependency versions from versioneye service')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command :

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
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');
        $projectName = $org.'/'.$repo;

        $client = $this->getApplication()->getVersionEyeClient();

        $results = $client->get('/api/v2/projects')->send();

        $projectId = '';
        $projectCollection = json_decode($results->getBody());

        foreach ($projectCollection as $project) {
            if ($project->name == $projectName) {
                $projectId = $project->id;
                break;
            }
        }

        if ('' == $projectId) {
            $output->writeln("Couldn't resolve the id of the project from the list from version eye.");

            return self::COMMAND_FAILURE;
        }

        $results = $client->get(sprintf('/api/v2/projects/%s', $projectId))->send();

        $response = json_decode($results->getBody());
        foreach ($response->dependencies as $dependency) {
            if ($dependency->outdated) {
                $this->getHelper('process')->runCommands(
                    [
                        [
                            'line' => sprintf(
                                'composer require %s %s --no-update',
                                $dependency->name,
                                $dependency->version_current
                            ),
                            'allow_failures' => true,
                        ]
                    ]
                );
            }
        }

        $output->writeln("Please check the modifications on your composer.json for\nupdated dependencies.");

        return self::COMMAND_SUCCESS;
    }
}
