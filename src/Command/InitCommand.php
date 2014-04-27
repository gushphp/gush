<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Initializes a local config
 *
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class InitCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Creates a local .gush.yml config file')
            ->addOption(
                'adapter',
                'a',
                InputOption::VALUE_OPTIONAL,
                "What adapter should be used? (github, bitbucket, gitlab)"
            )
            ->addOption(
                'meta',
                'm',
                InputOption::VALUE_NONE,
                "Add a local meta template"
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> configure parameters Gush will use:

    <info>$ gush %command.full_name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();
        $config = $application->getConfig();
        $adapters = $application->getAdapters();
        $adapterName = $input->getOption('adapter');

        $filename = $config->get('local_config');

        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');

        if (null === $adapterName) {
            $selection = $dialog->select(
                $output,
                'Choose adapter: ',
                array_keys($adapters),
                0
            );

            $adapterName = array_keys($adapters)[$selection];
        } elseif (!array_key_exists($adapterName, $adapters)) {
            throw new \Exception(
                sprintf(
                    'The adapter "%s" is invalid. Available adapters is "%s"',
                    $adapterName,
                    implode('", "',array_keys($adapters))
                )
            );
        }

        $adapterClass = $config->get(sprintf('[adapters][%s][adapter_class]', $adapterName));

        if (null === $adapterClass) {
            throw new \Exception(
                sprintf('The adapter "%s" is not yet configured. Please run the core:configure command', $adapterName)
            );
        }

        $params = [
            'adapter' => $adapterName
        ];

        if ($input->getOption('meta')) {
            $params['meta-header'] = $this->getMetaHeader($output);
        }

        if (file_exists($filename)) {
            $params = array_merge(Yaml::parse(file_get_contents($filename)), $params);
        }

        if (!@file_put_contents($filename, Yaml::dump($params), 0644)) {
            $output->writeln('<error>Configuration file cannot be saved.</error>');
        }

        $output->writeln('<info>Configuration file saved successfully.</info>');

        return self::COMMAND_SUCCESS;
    }

    private function getMetaHeader($output)
    {
        $template = $this->getHelper('template');
        $available = $template->getNamesForDomain('meta-header');
        $dialog = $this->getHelper('dialog');

        $selection = $dialog->select(
            $output,
            'Choose License: ',
            $available
        );

        $metaHeader = $this->getHelper('template')->askAndRender($output, 'meta-header', $available[$selection]);

        return $metaHeader;
    }
}
