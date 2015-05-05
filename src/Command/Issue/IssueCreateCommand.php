<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Issue;

use Gush\Command\BaseCommand;
use Gush\Exception\UserException;
use Gush\Feature\IssueTrackerRepoFeature;
use Gush\Helper\EditorHelper;
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IssueCreateCommand extends BaseCommand implements IssueTrackerRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:create')
            ->setDescription('Creates an issue')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Issue Title')
            ->addOption('body', null, InputOption::VALUE_REQUIRED, 'Issue Body')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command creates a new issue for either the current or the given organization
and repository:

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $emptyValidator = function ($string) {
            if (trim($string) === '') {
                throw new \InvalidArgumentException('This value cannot be empty');
            }

            return $string;
        };

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        if ('' === (string) $input->getOption('title')) {
            $input->setOption('title', $styleHelper->ask('Issue title', null, $emptyValidator));
        }

        if ('' === (string) $input->getOption('body')) {
            $body = $styleHelper->ask('Body (enter "e" to open editor)', '');

            if ('e' === $body) {
                /** @var EditorHelper $editor */
            $editor = $this->getHelper('editor');
                $body = $editor->fromString('');
            }

            $input->setOption('body', $body);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tracker = $this->getIssueTracker();
        $title = trim($input->getOption('title'));
        $body = $input->getOption('body');

        if ('' === $title) {
            throw new UserException(
                'Issue title cannot be empty, use the --title option to specify a title or use the interactive editor.'
            );
        }

        if (!$this->getParameter($input, 'remove-promote')) {
            $body = $this->appendPlug($body);
        }

        $issue = $tracker->openIssue($title, $body);
        $url = $tracker->getIssueUrl($issue);

        $this->getHelper('gush_style')->success("Created issue {$url}");

        return self::COMMAND_SUCCESS;
    }
}
