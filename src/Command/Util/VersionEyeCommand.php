<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Util;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class VersionEyeCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('version-eye:check')
            ->addOption('stable', null, InputOption::VALUE_NONE, 'Only update dependencies that are stable')
            ->setDescription('Update composer.json dependency versions from versioneye service')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command check status of dependencies at version-eye.com and applies
changes for an upgrade:

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
        $stable = $input->getOption('stable');

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
            $output->writeln(
                "<comment>Couldn't resolve the id of the project from the list from version eye.</comment>"
            );

            $questionHelper = $this->getHelper('question');

            $project = $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Please choose one of the available projects: ',
                    array_map(
                        function ($value) {
                            return $value->name;
                        },
                        $projectCollection
                    )
                )
            );

            $projectId = $projectCollection[$project]->id;
        }

        $results = $client->get(sprintf('/api/v2/projects/%s', $projectId))->send();

        $response = json_decode($results->getBody());

        foreach ($response->dependencies as $dependency) {
            if ($dependency->outdated) {

                if ($stable && !$dependency->stable) {
                    continue;
                }

                $this->getHelper('process')->runCommands(
                    [
                        [
                            'line' => sprintf(
                                'composer require %s %s --no-update',
                                $dependency->name,
                                $dependency->version_current
                            ),
                            'allow_failures' => true,
                        ],
                    ]
                );
            }
        }

        $output->writeln('Please check the modifications on your composer.json for updated dependencies.');

        return self::COMMAND_SUCCESS;
    }
}
