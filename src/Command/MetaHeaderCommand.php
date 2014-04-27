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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Gush\Feature\TemplateFeature;

class MetaHeaderCommand extends BaseCommand implements TemplateFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('meta:header')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not change anything, output files')
            ->addOption(
                'no-local',
                null,
                InputOption::VALUE_NONE,
                'Do not use the local meta header if it is available'
            )
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command asserts that headers are present
in files matching the given filter in the current git repository.

Supported Files:
    - php
    - js
    - css
    - twig
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateDomain()
    {
        return 'meta-header';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateDefault()
    {
        return 'mit';
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        $template = $input->getOption('template');

        $config = $this->getApplication()->getConfig();

        if (null === ($metaHeader = $config->get('meta-header')) || $input->getOption('no-local')) {
            $metaHeader = $this->getHelper('template')->askAndRender($output, 'meta-header', $template);
        }

        $allFiles = $this->getHelper('git')->listFiles();
        $meta = $this->getHelper('meta');

        $supportedTypes = array_keys($meta->getSupportedFiles());

        foreach ($supportedTypes as $type) {
            $files = array_filter($allFiles, function ($value) use ($type) {
                return pathinfo($value, PATHINFO_EXTENSION) === $type;
            });

            if (0 === count($files)) {
                continue;
            }

            $output->writeln([ '', sprintf('<info>Updating %s files:</info>', $type), '']);

            $header = $meta->renderHeader($metaHeader, $type);

            $output->writeln(
                [
                    '',
                    sprintf('<info>The following header will be set on %d files:</info>', count($files)),
                    '',
                    $header,
                ]
            );

            $confirmed = $this->getHelper('dialog')->askConfirmation(
                $output,
                '<question>Do you want to continue?</question> (y/n) ',
                true
            );

            if (!$confirmed) {
                continue;
            }

            $metaClass = $meta->getMetaClass($type);

            foreach ($files as $file) {
                $handler = fopen($file, 'r');

                $newLines = [];
                $headerAdded = false;

                $replace = true;

                while ($line = fgets($handler)) {

                    $trimmedLine = trim($line);

                    if (false === $headerAdded) {
                        if (preg_match('&^'.preg_quote($metaClass->getStartDelimiter()).'?&', $trimmedLine)) {
                            $headerAdded = true;

                            while ($headerLine = fgets($handler)) {
                                if (!preg_match('&^ ?'.preg_quote($metaClass->getDelimiter()).'&', $headerLine)) {

                                    if (true === $replace) {
                                        $newLines[] = $header;
                                        $headerAdded = true;
                                    }
                                    continue 2;
                                }
                            }
                        }

                        if (!in_array($trimmedLine, ['<?php', '<?']) && $trimmedLine != '') {
                            $newLines[] = $header;
                            $newLines[] = $line;
                            $headerAdded = true;
                            continue;
                        }
                    }

                    $newLines[] = $line;
                }

                if (false === $dryRun) {
                    file_put_contents($file, implode('', $newLines));
                }

                $output->writeln(
                    sprintf(
                        '%s<info>Updating header in file "%s"</info>',
                        $dryRun === false ? '' : ' <comment>[DRY-RUN] </comment>',
                        $file
                    )
                );
            }
        }

        return self::COMMAND_SUCCESS;
    }
}
