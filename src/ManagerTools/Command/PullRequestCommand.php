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

use ManagerTools\Model\BufferedOutput;
use ManagerTools\Model\Question;
use ManagerTools\Model\Questionary;
use ManagerTools\Model\SymfonyQuestionary;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pr')
            ->setDescription('Pull request command')
            ->addArgument('baseBranch', InputArgument::OPTIONAL, 'Name of the base branch to PR', 'master')
            ->addArgument('org', InputArgument::OPTIONAL, 'Name of the GitHub organization', $this->getVendorName())
            ->addArgument('repo', InputArgument::OPTIONAL, 'Name of the GitHub repository', $this->getRepoName())
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tableString = $this->getGithubTableString($output);
        $prNumber = $this->postPullRequest($input, $output, $tableString);
    }

    /**
     * @param  OutputInterface $output
     * @return string
     */
    protected function getGithubTableString(OutputInterface $output)
    {
        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');

        /** @var \ManagerTools\Model\Question[] $questions */
        $questionary = new SymfonyQuestionary();

        $answers = array();
        /** @var Question $question */
        foreach ($questionary->getQuestions() as $question) {
            $statement = $question->getStatement() . ' ';
            if ($question->getDefault()) {
                $statement .= '[' . $question->getDefault() . '] ';
            }

            // change this when on 2.5 to the new Question model
            $answers[] = array(
                $question->getStatement(),
                $dialog->askAndValidate(
                    $output,
                    $statement,
                    $question->getValidator(),
                    $question->getAttempt(),
                    $question->getDefault(),
                    $question->getAutocomplete()
                )
            );
        }

        $table = $this->getMarkdownTableHelper($questionary);
        $table->addRows($answers);

        $tableOutput = new BufferedOutput();
        $table->render($tableOutput);

        return $tableOutput->fetch();
    }

    /**
     * @param  Questionary $questionary
     * @return TableHelper
     */
    protected function getMarkdownTableHelper(Questionary $questionary)
    {
        $table = $this->getHelper('table');

        /** @var TableHelper $table */
        $table
            ->setLayout(TableHelper::LAYOUT_DEFAULT)
            ->setVerticalBorderChar('|')
            ->setHorizontalBorderChar(' ')
            ->setCrossingChar(' ')
        ;

        // adds headers from questionary
        $table->addRow($questionary->getHeaders());
        // add rows --- | --- | ...
        $table->addRow(array_fill(0, count($questionary->getHeaders()), '---'));

        return $table;
    }

    /**
     * @param InputInterface $input
     * @param  OutputInterface $output
     * @param  string $description
     * @return mixed
     */
    protected function postPullRequest(InputInterface $input, OutputInterface $output, $description)
    {
        $repo = $input->getArgument('repo');
        $org = $input->getArgument('org');
        $baseBranch = $input->getArgument('baseBranch');

        $github = $this->getParameter('github');
        $username = $github['username'];
        $branchName = $this->getBranchName();

        // hard coded now but possibly prompt QA or default to single commit message
        $title = 'Manager Tools Sample Title (change me)';

        $commands = array(
            array(
                'line' => sprintf('git remote add %s git@github.com:%s/%s.git', $username, $username, $repo),
                'allow_failures' => true
            ),
            array(
                'line' => 'git remote update',
                'allow_failures' => false
            ),
            array(
                'line' => sprintf('git push -u %s %s', $username, $branchName),
                'allow_failures' => false
            )
        );

        foreach ($commands as $command) {
            $this->runItem($explodedCommand = explode(' ', $command['line']), $command['allow_failures']);
        }

        $client = $this->getGithubClient();
        $pullRequest = $client
            ->api('pull_request')
            ->create($org, $repo, array(
                    'base'  => $org.':'.$baseBranch,
                    'head'  => $username.':'.$branchName,
                    'title' => $title,
                    'body'  => $description
                )
            )
        ;

        return $pullRequest;
    }
}
