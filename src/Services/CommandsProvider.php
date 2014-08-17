<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Services;

use Gush\Command as Cmd;
use KevinGH\Amend\Command as UpdateCommand;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class CommandsProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['commands'] = function ($c) {
            $updateCommand = new UpdateCommand('core:update');
            $updateCommand->setManifestUri($c['application.manifesto_url']);

            return [
                $updateCommand,
                new Cmd\PullRequest\PullRequestCreateCommand(),
                new Cmd\PullRequest\PullRequestMergeCommand(),
                new Cmd\PullRequest\PullRequestCloseCommand(),
                new Cmd\PullRequest\PullRequestPatOnTheBackCommand(),
                new Cmd\PullRequest\PullRequestAssignCommand(),
                new Cmd\PullRequest\PullRequestSwitchBaseCommand(),
                new Cmd\PullRequest\PullRequestSquashCommand(),
                new Cmd\PullRequest\PullRequestSemVerCommand(),
                new Cmd\PullRequest\PullRequestListCommand(),
                new Cmd\PullRequest\PullRequestLabelListCommand(),
                new Cmd\PullRequest\PullRequestMilestoneListCommand(),
                new Cmd\PullRequest\PullRequestFixerCommand(),
                new Cmd\Util\VersionEyeCommand(),
                new Cmd\Util\FabbotIoCommand(),
                new Cmd\Util\MetaHeaderCommand(),
                new Cmd\Release\ReleaseCreateCommand(),
                new Cmd\Release\ReleaseListCommand(),
                new Cmd\Release\ReleaseRemoveCommand(),
                new Cmd\Issue\IssueTakeCommand(),
                new Cmd\Issue\IssueCreateCommand(),
                new Cmd\Issue\IssueCloseCommand(),
                new Cmd\Issue\IssueAssignCommand(),
                new Cmd\Issue\IssueLabelListCommand(),
                new Cmd\Issue\IssueMilestoneListCommand(),
                new Cmd\Issue\IssueShowCommand(),
                new Cmd\Issue\IssueListCommand(),
                new Cmd\Issue\LabelIssuesCommand(),
                new Cmd\Branch\BranchPushCommand(),
                new Cmd\Branch\BranchSyncCommand(),
                new Cmd\Branch\BranchDeleteCommand(),
                new Cmd\Branch\BranchForkCommand(),
                new Cmd\Branch\BranchChangelogCommand(),
                new Cmd\Branch\BranchRemoteAddCommand(),
                new Cmd\Core\CoreConfigureCommand(),
                new Cmd\Core\CoreAliasCommand(),
                new Cmd\Core\InitCommand(),
                new Cmd\Core\AutocompleteCommand(),
            ];
        };
    }
}
