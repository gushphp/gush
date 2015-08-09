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
use Gush\Config;
use Gush\ConfigFactory;
use Gush\Feature\GitFolderFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class MetaConfigureCommand extends BaseCommand implements GitFolderFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('meta:configure')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> updates the "meta-header" configuration in your local .gush.yml file.
This information is used by the <info>$ gush meta:header</info> for applying the correct header on all files.

    <info>$ gush %command.name%</info>

EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getConfig()->set('meta-header', $this->getMetaHeader(), Config::CONFIG_LOCAL);
        ConfigFactory::dumpToFile($this->getConfig(), Config::CONFIG_LOCAL);

        $this->getHelper('gush_style')->success('Configuration file saved successfully.');

        return self::COMMAND_SUCCESS;
    }

    private function getMetaHeader()
    {
        /** @var \Gush\Helper\TemplateHelper $template */
        $template = $this->getHelper('template');
        $available = $template->getNamesForDomain('meta-header');

        $licenseSelection = $this->getHelper('gush_style')->askQuestion(
            new ChoiceQuestion('Choose License', $available)
        );

        return $this->getHelper('template')->askAndRender('meta-header', $licenseSelection);
    }
}
