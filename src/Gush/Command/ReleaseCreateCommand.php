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

use Github\Client;
use Gush\Exception\FileNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Gush\Feature\GitHubFeature;

class ReleaseCreateCommand extends BaseCommand implements GitHubFeature
{
    protected $workDir;

    protected function configure()
    {
        $this
            ->setName('release:create')
            ->setDescription('Create a new Release')
            ->addArgument('tag', InputArgument::REQUIRED, 'Tag of the release')

            ->addOption('target-commitish', null, InputOption::VALUE_REQUIRED, 'Commitish/ref to create the tag from')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the release')
            ->addOption('body', null, InputOption::VALUE_REQUIRED, 'Description of the release')
            ->addOption('draft', null, InputOption::VALUE_NONE, 'Specify to create an unpublished release')
            ->addOption(
                'prerelease',
                null,
                InputOption::VALUE_NONE,
                'Specify to create a pre-release, omit for full release'
            )
            ->addOption(
                'asset-file',
                null,
                InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
                'Assets to include in this release'
            )
            ->addOption(
                'asset-name',
                null,
                InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
                'Names corresponding to asset-files'
            )
            ->addOption(
                'asset-content-type',
                null,
                InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
                'Content types corresponding to asset-files (default: application/zip)'
            )
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Replace any existing release with the same name')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null
     *
     * @throws \Gush\Exception\FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getApplication()->getGithubClient();
        $releaseName = $input->getOption('name');
        $tag = $input->getArgument('tag');
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');
        $assetFiles = $input->getOption('asset-file');
        $assetNames = $input->getOption('asset-name');
        $assetContentTypes = $input->getOption('asset-content-type');

        if ($input->getOption('replace')) {
            $this->removeExisting($output, $client, $org, $repo, $tag);
        }

        // validate assets
        foreach ($assetFiles as $assetFile) {
            if (!file_exists($assetFile)) {
                throw new FileNotFoundException(sprintf('Asset "%s" does not exist', $assetFile));
            }
        }

        $output->writeln(
            sprintf(
                '<info>Creating release %s for </info>%s<info> on </info>%s<info>/</info>%s',
                $releaseName,
                $tag,
                $org,
                $repo
            )
        );

        $release = $client->api('repo')->releases()->create($org, $repo, [
            'tag_name' => $input->getArgument('tag'),
            'target_commitish' => $input->getOption('target-commitish'),
            'name' => $input->getOption('name'),
            'body' => $input->getOption('body'),
            'draft' => $input->getOption('draft'),
            'prerelease' => $input->getOption('prerelease'),
        ]);

        $output->writeln(sprintf('<info>Created release with ID </info>%s', $release['id']));

        foreach ($assetFiles as $i => $assetFile) {
            $output->writeln(
                sprintf('<info>Uploading asset </info>%s"', $assetFile)
            );

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
                $org,
                $repo,
                $release['id'],
                $assetName,
                $assetContentType,
                $content
            );
        }
    }

    protected function removeExisting(OutputInterface $output, Client $client, $org, $repo, $tag)
    {
        $releases = $client->api('repo')->releases()->all($org, $repo);
        $id = null;

        foreach ($releases as $release) {
            if ($tag == $release['tag_name']) {
                $id = $release['id'];
            }
        }

        if ($id) {
            $output->writeln(sprintf('<info>Removing existing release with tag </info>%s (id: %s)', $tag, $id));
            $client->api('repo')->releases()->remove($org, $repo, $id);
        }
    }
}
