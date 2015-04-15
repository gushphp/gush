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

use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Api\IO\IO;

class BranchDeleteCommandHandler implements GitRepoFeature
{
    protected function handle(Args $args, IO $io, Command $command)
    {
//        if (!$currentBranchName = $input->getArgument('branch_name')) {
//            $currentBranchName = $this->getHelper('git')->getActiveBranchName();
//        }
//
//        $org = $input->getArgument('other_organization');
//        if (null === $org) {
//            $org = $this->getParameter('authentication')['username'];
//        }
//
//        $this->getHelper('git')->pushToRemote($org, ':'.$currentBranchName, true);
//
//        $this->getHelper('gush_style')->success(
//            sprintf('Branch %s/%s has been deleted!', $org, $currentBranchName)
//        );

        echo 'echo';

        return 0;
    }
}
