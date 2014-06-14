<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Core;

use Gush\Command\BaseCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Initializes a local config
 *
 * @author Pierre du Plessis <pdples@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class InitCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:init')
            ->setDescription('Configures a local .gush.yml config file')
            ->addOption(
                'adapter',
                'a',
                InputOption::VALUE_OPTIONAL,
                'What adapter should be used? (github, bitbucket, gitlab)'
            )
            ->addOption(
                'issue tracker',
                'it',
                InputOption::VALUE_OPTIONAL,
                'What issue tracker should be used? (jira, github, bitbucket, gitlab)'
            )
            ->addOption(
                'meta',
                'm',
                InputOption::VALUE_NONE,
                'Add a local meta template'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> configure parameters Gush will use:

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
        $application = $this->getApplication();
        /** @var \Gush\Application $application */
        $config = $application->getConfig();
        $adapters = $application->getAdapterFactory()->getAdapters();
        $issueTrackers = $application->getAdapterFactory()->getIssueTrackers();
        $adapterName = $input->getOption('adapter');
        $issueTrackerName = $input->getOption('issue tracker');

        $filename = $config->get('local_config');

        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');

        if (null === $adapterName) {
            $adaptersIdxs = array_keys($adapters);
            $currentValue = (int) array_search($config->get('adapter'), $adaptersIdxs);

            $selection = $dialog->select(
                $output,
                'Choose adapter: ',
                $adaptersIdxs,
                $currentValue
            );

            $adapterName = $adaptersIdxs[$selection];
        } elseif (!array_key_exists($adapterName, $adapters)) {
            throw new \Exception(
                sprintf(
                    'Adapter "%s" is invalid. Available adapters are "%s"',
                    $adapterName,
                    implode('", "', array_keys($adapters))
                )
            );
        }

        if (!$config->has(sprintf('[adapters][%s]', $adapterName))) {
            throw new \Exception(
                sprintf(
                    'Adapter "%s" is not yet configured. Please run the "core:configure" command.',
                    $adapterName
                )
            );
        }

        if (null === $issueTrackerName) {
            $issueTrackerIdxs = array_keys($issueTrackers);
            $currentValue = (int) array_search($config->get('issue_tracker'), $issueTrackerIdxs);

            $selection = $dialog->select(
                $output,
                'Choose issue tracker: ',
                $issueTrackerIdxs,
                $currentValue
            );

            $issueTrackerName = $issueTrackerIdxs[$selection];
        } elseif (!array_key_exists($issueTrackerName, $issueTrackers)) {
            throw new \Exception(
                sprintf(
                    'Issue tracker "%s" is invalid. Available adapters are "%s".',
                    $issueTrackerName,
                    implode('", "', array_keys($issueTrackers))
                )
            );
        }

        if (!$config->has(sprintf('[issue_trackers][%s]', $issueTrackerName))) {
            throw new \Exception(
                sprintf(
                    'The issue tracker "%s" is not yet configured. Please run the "core:configure" command.',
                    $issueTrackerName
                )
            );
        }

        $params = [
            'adapter' => $adapterName,
            'issue_tracker' => $issueTrackerName,
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

        return $this->getHelper('template')
            ->askAndRender($output, 'meta-header', $available[$selection])
        ;
    }
}
