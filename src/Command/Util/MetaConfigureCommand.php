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

use Gush\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Yaml\Yaml;

class MetaConfigureCommand extends BaseCommand
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
        $application = $this->getApplication();
        /** @var \Gush\Application $application */
        $config = $application->getConfig();
        $filename = $config->get('local_config');

        $params = [];
        if (file_exists($filename)) {
            $params = Yaml::parse(file_get_contents($filename));
        }

        $params['meta-header'] = $this->getMetaHeader($input, $output);

        if (!@file_put_contents($filename, Yaml::dump($params), 0644)) {
            $output->writeln('<error>Configuration file cannot be saved.</error>');
        }

        $output->writeln('<info>Configuration file saved successfully.</info>');

        return self::COMMAND_SUCCESS;
    }

    private function getMetaHeader($input, $output)
    {
        $template = $this->getHelper('template');
        /** @var \Gush\Helper\TemplateHelper $template */
        $available = $template->getNamesForDomain('meta-header');
        $questionHelper = $this->getHelper('question');

        $licenseSelection = $questionHelper->ask(
            $input,
            $output,
            new ChoiceQuestion('Choose License: ', $available)
        );

        return $this->getHelper('template')->askAndRender($output, 'meta-header', $licenseSelection);
    }
}
