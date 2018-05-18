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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestCloseCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:close')
            ->setDescription('Closes a pull request')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull Request number to be closed')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Closing comment')
            ->addOption('remove-source-branch', null, InputOption::VALUE_REQUIRED, 'Remove remote source branch after closing own pull request', 'no')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command closes a Pull Request for either the current or the given organization
and repository:

    <info>$ gush %command.name% 12 -m"let's try to keep it low profile guys." --remove-source-branch=yes</info>

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
        $prNumber = $input->getArgument('pr_number');
        $pr = $adapter->getPullRequest($prNumber);
        $authenticatedUser = $this->getParameter($input, 'authentication')['username'];
        $removeSourceBranch = $input->getOption('remove-source-branch');
        if ('yes' === $removeSourceBranch && $pr['user'] !== $authenticatedUser) {
            throw new UserException(sprintf('`--remove-source-branch` option cannot be used with pull requests that aren\'t owned by the authenticated user (%s)', $authenticatedUser));
        }

        $closingComment = $input->getOption('message');
        $adapter->closePullRequest($prNumber);

        if ($input->getOption('message')) {
            $adapter->createComment($prNumber, $closingComment);
        }

        $url = $adapter->getPullRequest($prNumber)['url'];
        $this->getHelper('gush_style')->success("Closed {$url}");

        // Post close options
        if ($pr['user'] === $authenticatedUser) {
            if ('yes' !== $removeSourceBranch) {
                $removeSourceBranch = $this->getHelper('gush_style')->choice('Delete source branch?', ['yes', 'no'], 'no');
            }
            if ('yes' === $removeSourceBranch) {
                $adapter->removePullRequestSourceBranch($pr['number']);
                $this->getHelper('gush_style')->note(sprintf('Remote source branch %s:%s has been removed.', $pr['head']['user'], $pr['head']['ref']));
            }
        }

        return self::COMMAND_SUCCESS;
    }
}
