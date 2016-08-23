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
use Gush\Exception\InvalidStateException;
use Gush\Feature\GitRepoFeature;
use Gush\Feature\TableFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestShowCommand extends BaseCommand implements TableFeature, GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    public function getTableDefaultLayout()
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:show')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Identifier of the PR to retrieve'
            )
            ->addOption(
                'with-comments',
                null,
                InputOption::VALUE_NONE,
                'Display comments from this pull request'
            )
            ->setDescription('Retrieve detail for a specific pull request')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command retrieves details for a pull requests:

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
        $id = $input->getArgument('id');
        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($id);

        $styleHelper = $this->getHelper('gush_style');
        $styleHelper->title(
            sprintf(
                'Pull Request #%s - %s by %s [<fg='.'%s>%s</>]',
                $pr['number'],
                $pr['title'],
                $pr['user'],
                'closed' === $pr['state'] ? 'red' : 'green',
                $pr['state']
            )
        );

        $styleHelper->detailsTable(
            [
                ['Org/Repo', $input->getOption('org').'/'.$input->getOption('repo')],
                ['Link', $pr['url']],
                ['Labels', implode(', ', $pr['labels']) ?: '<comment>None</comment>'],
                ['Milestone', $pr['milestone'] ?: '<comment>None</comment>'],
                ['Assignee', $pr['assignee'] ?: '<comment>None</comment>'],
                ['Source => Target', $pr['head']['user'].'/'.$pr['head']['repo'].'#'.$pr['head']['ref'].' => '.$pr['base']['user'].'/'.$pr['base']['repo'].'#'.$pr['base']['ref']],
            ]
        );

        $styleHelper->section('Body');
        $styleHelper->text(explode("\n", $pr['body']));

        if (true === $input->getOption('with-comments')) {
            $comments = $adapter->getComments($id);
            $styleHelper->section('Comments');
            foreach ($comments as $comment) {
                $styleHelper->text(sprintf(
                    '<fg=white>Comment #%s</> by %s on %s',
                    $comment['id'],
                    $comment['user'],
                    empty($comment['created_at'])?'':$comment['created_at']->format('r')
                ));
                $styleHelper->detailsTable([
                    ['Link', $comment['url']],
                ]);
                $styleHelper->text(explode("\n", wordwrap($comment['body'], 100)));
                $styleHelper->text([]);
            }
        }

        return self::COMMAND_SUCCESS;
    }
}
