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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copy an issue from one repository to another.
 */
class IssueCopyCommand extends BaseCommand implements IssueTrackerRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:copy')
            ->setDescription('Copy issue')
            ->addArgument('issue_number', InputArgument::REQUIRED, 'Issue number to move')
            ->addArgument('target_username', InputArgument::REQUIRED, 'Target username or organization')
            ->addArgument('target_repository', InputArgument::REQUIRED, 'Target repository')
            ->addOption(
                'target-adapter',
                'ta',
                InputOption::VALUE_OPTIONAL,
                'Adapter-name of the target issue-manager'
            )
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Prefix for the issue title')
            ->addOption('close', null, InputOption::VALUE_NONE, 'Close original issue')
            ->addOption('with-comments', null, InputOption::VALUE_NONE, 'Also copy comments')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command moves an issue from one repository to another

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueNumber = $input->getArgument('issue_number');
        $targetUsername = $input->getArgument('target_username');
        $targetRepository = $input->getArgument('target_repository');
        $prefix = $input->getOption('prefix') ?: '';
        $close = $input->getOption('close');

        $adapter = $this->getIssueTracker();
        if ($input->getOption('target-adapter') !== null && $input->getOption('target-adapter') !== $input->getOption('issue-adapter')) {
            $destAdapter = $this->buildIssueAdapter(
                $input->getOption('target-adapter')
            );
        } else {
            $destAdapter = clone $adapter;
        }

        $srcIssue = $adapter->getIssue($issueNumber);
        $srcTitle = $prefix.$srcIssue['title'];
        $srcUsername = $adapter->getUsername();
        $srcRepository = $adapter->getRepository();

        $destAdapter->setUsername($targetUsername);
        $destAdapter->setRepository($targetRepository);
        $destIssueNumber = $destAdapter->openIssue(
            $srcTitle,
            $srcIssue['body'],
            $srcIssue
        );
        $issueUrl = $destAdapter->getIssueUrl($destIssueNumber);

        $this->getHelper('gush_style')->success(
            sprintf('Opened issue: %s', $issueUrl)
        );

        if (true === $input->getOption('with-comments')) {
            $comments = $adapter->getComments($issueNumber);
            uasort($comments, function ($a, $b) {
                if ($a['created_at'] == $b['created_at']) {
                    return 0;
                }

                return ($a['created_at'] < $b['created_at']) ? -1 : 1;
            });

            $messages = [];
            foreach ($comments as $comment) {
                $commentUrl = $destAdapter->createComment(
                    $destIssueNumber,
                    sprintf(
                        "%s\n\nby **%s** on **%s**",
                        $comment['body'],
                        $comment['user']['login'],
                        $comment['created_at']->format('r')
                    )
                );
                $messages[] = sprintf(
                    'Comment added: %s',
                    is_array($commentUrl) ? implode(',', $commentUrl) : $commentUrl
                );
            }
            $this->getHelper('gush_style')->listing($messages);
        }

        $adapter->setUsername($srcUsername);
        $adapter->setRepository($srcRepository);

        if (true === $close) {
            if ('closed' === $srcIssue['state']) {
                $this->getHelper('gush_style')->error(
                    sprintf(
                        'Issue #%d was already closed.',
                        $issueNumber
                    )
                );
            } else {
                $adapter->closeIssue($issueNumber);

                $this->getHelper('gush_style')->success(
                    sprintf(
                        'Closed issue: %s',
                        $adapter->getIssueUrl($issueNumber)
                    )
                );
            }
        }

        return self::COMMAND_SUCCESS;
    }

    /**
     * Build a valid IssueTracker instance.
     *
     * @param string $name Adapter name to build
     *
     * @return Gush\Adapter\IssueTracker
     */
    protected function buildIssueAdapter($name)
    {
        $issueAdapter = $this
            ->getApplication()
            ->getAdapterFactory()
            ->createIssueTracker(
                $name,
                $this->getConfig()->get(
                    ['adapters', $name],
                    \Gush\Config::CONFIG_SYSTEM
                ),
                $this->getConfig()
            );
        $issueAdapter->authenticate();

        return $issueAdapter;
    }
}
