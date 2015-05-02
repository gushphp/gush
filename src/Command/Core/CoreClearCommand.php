<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Core;

use Gush\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class CoreClearCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('core:clear')
            ->setDescription('Clears cache from cache folder')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> clears files from cache folder:

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
        $cacheDir = $this->getConfig()->get('cache-dir');

        // Confirmation is used to ensure the user knows which directory will be deleted.
        // If cache dir is set directly to the system TEMP (not /tmp/gush) it should not be deleted!
        if ($input->isInteractive() &&
            !$this->getHelper('gush_style')->confirm(sprintf('Remove/clear directory "%s"?', $cacheDir), false)
        ) {
            $this->getHelper('gush_style')->error('User aborted.');

            return self::COMMAND_FAILURE;
        }

        (new Filesystem())->remove($cacheDir);

        $this->getHelper('gush_style')->success('Cache has been cleared.');

        return self::COMMAND_SUCCESS;
    }
}
