<?php

/*
 * This file is part of the Manager Tools.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ManagerTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ReleaseCreateCommand extends Command
{
    protected $workDir;

    protected function configure()
    {
        $this->setName('release:create')
            ->setDescription('Create Release')
            ;
        $this->addArgument('org', InputArgument::REQUIRED, 'Name of GITHub organization');
        $this->addArgument('repo', InputArgument::REQUIRED, 'Name of GITHub repository');
        $this->addArgument('tag', InputArgument::REQUIRED, 'Tag of release');
        $this->addOption('target-commitish', null, InputOption::VALUE_REQUIRED, 'Commitish/ref to create tag from');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of release');
        $this->addOption('body', null, InputOption::VALUE_REQUIRED, 'Description of release');
        $this->addOption('draft', null, InputOption::VALUE_NONE, 'Specify to create an unpublished release');
        $this->addOption('prerelease', null, InputOption::VALUE_NONE, 'Specify to create a pre-release, ommit for full release');
        $this->addOption('asset-file', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Assets to include in this release');
        $this->addOption('asset-name', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Names corresponding to asset-files');
        $this->addOption('asset-content-type', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Content types corresponding to asset-files (default to application/zip');

    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getApplication()->getGithubClient();
        $releaseName = $input->getOption('name');
        $tag = $input->getArgument('tag');
        $repo = $input->getArgument('repo');
        $org = $input->getArgument('org');
        $assetFiles = $input->getOption('asset-file');
        $assetNames = $input->getOption('asset-name');
        $assetContentTypes = $input->getOption('asset-content-type');

        // validate assets
        foreach ($assetFiles as $assetFile) {
            if (!file_exists($assetFile)) {
                throw new \InvalidArgumentException(sprintf(
                    'Asset "%s" does not exist', $assetFile
                ));
            }
        }

        $output->writeln(sprintf(
            '<info>Creating release for </info>%s<info> on </info>%s<info>/</info>%s', 
            $tag, $org, $repo
        ));

        $release = $client->api('repo')->releases()->create($org, $repo, array(
            'tag_name' => $input->getArgument('tag'),
            'target_commitish' => $input->getOption('target-commitish'),
            'name' => $input->getOption('name'),
            'body' => $input->getOption('body'),
            'draft' => $input->getOption('draft'),
            'prerelease' => $input->getOption('prerelease'),
        ));

        $output->writeln(sprintf('<info>Created release with ID </info>%s', $release['id']));

        foreach ($assetFiles as $i => $assetFile) {
            $output->writeln(sprintf(
                '<info>Uploading asset </info>%s"', $assetFile
            ));

            if (isset($assetNames[$i])) {
                $assetName = $assetNames[$i];
            } else {
                $assetName = basename($assetFile);
            }

            if (isset($assetContentTypes[$i])) {
                $assetContentType = $assetContentTypes[$i];
            } else {
                $assetContentType = 'application/zip';
            }

            $content = file_get_contents($assetFile);
            $client->api('repo')->releases()->assets()->create(
                $org, $repo, $release['id'], $assetName, $assetContentType, $content
            );
        }
    }
}
