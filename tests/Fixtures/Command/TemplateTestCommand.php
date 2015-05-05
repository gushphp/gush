<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Fixtures\Command;

use Gush\Command\BaseCommand;
use Gush\Feature\TableFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TemplateTestCommand extends BaseCommand implements TableFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('test:template-command')
            ->setDescription('Command that implements TableFeature')
            ->setHelp('')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return self::COMMAND_SUCCESS;
    }

    /**
     * Return the default table layout to use
     *
     * @return string
     */
    public function getTableDefaultLayout()
    {
        return 'default';
    }
}
