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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestMergeCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:merge')
            ->setDescription('Pull request command')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull Request number')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');
        $prNumber = $input->getArgument('pr_number');

        $client = $this->getGithubClient();

        $pr = $client->api('pull_request')->show($org, $repo, $prNumber);
        $commits = $client->api('pull_request')->commits($org, $repo, $prNumber);

        $message = $this->render(
            'merge',
            [
                'baseBranch' => $pr['base']['label'],
                'prTitle' => $pr['title'],
                'prBody' => $pr['body'],
                'commits' => $this->getCommitsString($commits)
            ]
        );

        $merge = $client->api('pull_request')->merge($org, $repo, $prNumber, $message);

        if ($merge['merged']) {
            $output->writeln($merge['message']);
        } else {
            $output->writeln('There was a problem merging: '.$merge['message']);
        }

        return self::COMMAND_SUCCESS;
    }

    protected function getCommitsString($commits)
    {
        $commitsString = '';
        foreach ($commits as $commit) {
            $commitsString .= sprintf(
                "%s %s %s\n",
                $commit['sha'],
                $commit['commit']['message'],
                $commit['author']['login']
            );
        }

        return $commitsString;
    }
}
