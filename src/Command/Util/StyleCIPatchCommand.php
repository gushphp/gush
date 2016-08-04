<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Util;

use Gush\Command\BaseCommand;
use Gush\Exception\UserException;
use Gush\Exception\WorkingTreeIsNotReady;
use Gush\Feature\GitDirectoryFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\DownloadHelper;
use Gush\Helper\GitHelper;
use Gush\ThirdParty\Github\GitHubAdapter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StyleCIPatchCommand extends BaseCommand implements GitRepoFeature, GitDirectoryFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:styleci')
            ->setDescription('Apply StyleCI patches on given PR')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command applies StyleCI patches on given PR:

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
        $adapter = $this->getAdapter();

        if (!$adapter instanceof GitHubAdapter) {
            throw new UserException('Usage of StyleCI is currently limited to the GitHub adapter.');
        }

        $org = $input->getOption('org');
        $repo = $input->getOption('repo');

        $prNumber = $input->getArgument('pr_number');
        $pr = $adapter->getPullRequest($prNumber);

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');

        if (!$gitHelper->isWorkingTreeReady()) {
            throw new WorkingTreeIsNotReady();
        }

        $status = $this->getStatus($adapter->getCommitStatuses($org, $repo, $pr['head']['sha']));

        if ('success' === $status['status']) {
            $this->getHelper('gush_style')->error('Nothing to update.');

            return self::COMMAND_SUCCESS;
        }

        /** @var DownloadHelper $downloadHelper */
        $downloadHelper = $this->getHelper('download');
        $patchFile = $downloadHelper->downloadFile($status['patch_url']);

        $patchOperation = $gitHelper->createRemotePatchOperation();
        $patchOperation->setRemote($pr['head']['user'], $pr['head']['ref']);
        $patchOperation->applyPatch($patchFile, 'correct CS', 'p1');
        $patchOperation->pushToRemote();

        $this->getHelper('gush_style')->success('StyleCI patch was applied and pushed.');

        return self::COMMAND_SUCCESS;
    }

    private function getStatus(array $statuses)
    {
        foreach ($statuses as $status) {
            if (false !== stripos($status['context'], 'StyleCI')) {
                return [
                    'status' => $status['state'],
                    'patch_url' => $status['target_url'].'/diff',
                ];
            }
        }

        throw new UserException(
            'No StyleCI status found in commit, make sure StyleCI is enabled for the repository.'."\n".
            'And that analyses exist for the commit/pull-request.'
        );
    }
}
