<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Handler;

use Webmozart\Console\Api\Args\Format\Argument;
use Webmozart\Console\Api\Args\Format\Option;
use Webmozart\Console\Api\Config\CommandConfig;

class BranchDeleteCommandConfig extends CommandConfig
{
    protected function configure()
    {
        $this
            ->beginCommand('branch:delete')
                ->setDescription('Deletes the current branch, or the branch with the given name')
                ->addArgument('branch_name', Argument::OPTIONAL, 'Optional branch name to delete')
                ->addArgument(
                    'organization',
                    Argument::OPTIONAL,
                    'Organization (defaults to username) where the branch will be deleted'
                )
                ->addOption(
                    'force',
                    null,
                    Option::NO_VALUE,
                    'Attempts to delete the branch even when permissions detected are insufficient'
                )
                ->setHelp(
                    <<<EOF
    The <info>%command.name%</info> command deletes the current or given remote branch on
    the organization (defaults to username):

        <info>$ gush %command.name%</info>

    Note: The "organization" argument defaults to your username (the forked repository) not
    the organization you would normally provide using the --org option.

    For security reasons it's not directly possible to delete the "master" branch,
    use the <comment>--force</comment> option to force a delete, use with caution!
EOF
                )
            ->end()
        ;
    }
}
