<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Util;

use Gush\Adapter\GitHubAdapter;
use Gush\Command\BaseCommand;
use Gush\Exception\WorkingTreeIsNotReady;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class FabbotIoCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:fabbot-io')
            ->setDescription('Run fabbot-io patches on given PR')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command applies patch fabbot-io robot on given PR:

    <info>$ gush %command.name% 12</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getAdapter() instanceof GitHubAdapter) {
            throw new \Exception('Usage of fabbot.io is currently limited to the GitHub adapter.');
        }

        $org = $input->getOption('org');
        $repo = $input->getOption('repo');

        $prNumber = $input->getArgument('pr_number');

        $github = $this->getParameter($input, 'authentication');
        $username = $github['username'];

        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);

        $gitHelper = $this->getHelper('git');
        /** @var GitHelper $gitHelper */

        if (!$gitHelper->isWorkingTreeReady()) {
            throw new WorkingTreeIsNotReady();
        }

        $gitHelper->checkout($pr['head']['ref']);

        $commandLine = sprintf(
            'curl http://fabbot.io/patch/%s/%s/%s/%s/cs.diff | patch -p0',
            $org,
            $repo,
            $prNumber,
            $pr['head']['sha']
        );

        // correct this after https://github.com/symfony/symfony/issues/10025 is solved
        $process = new Process($commandLine, getcwd());
        $process->run();

        $gitHelper->pushToRemote($username, $pr['head']['ref'], true, true);

        $this->getHelper('gush_style')->success('Fabbot.io patch was applied and pushed.');

        return self::COMMAND_SUCCESS;
    }
}
