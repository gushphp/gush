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
use Gush\Feature\TemplateFeature;
use Gush\Helper\MetaHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
The <info>%command.name%</info> command ensures that headers are present
in files matching the given filter in the current git repository.

Supported Files:
    - php
    - js
    - css
    - twig

If you don't want certain files or directories to be updated,
you can configure which paths must be excluded.

Add the following to your local <comment>.gush.yml</comment> file.
<comment>
meta-exclude:
    - lib/      # excludes all the files in the lib/ directory
    - meta/*.js # excludes all the js-files in the meta-folder
</comment>

Each value in the "meta-exclude" is either a Glob or complete regexp pattern
which excludes the path from being updated by the %command.name% command.

Please keep the following in mind with using patterns:

- paths are relative to your repository root-folder
- patterns are case-sensitive (unless you use regexp with the 'i' (insensitive) flag like "/lib\//i")
- patterns are applied to both directories and file-names (end related patterns with '/' to restrict to directories)

For more information on using the paths see: http://symfony.com/doc/current/components/finder.html#path

<info>Tip:</info> If you are not sure your patterns are correct, run the %command.name% command with
<comment>--dry-run</comment> to get a list of files that 'would' have been updated.

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
        /** @var \Gush\Config $config */

        if (null === ($metaHeader = $config->get('meta-header')) || $input->getOption('no-local')) {
            $metaHeader = $this->getHelper('template')->askAndRender($output, 'meta-header', $template);
        }

        $allFiles = $this->getHelper('git')->listFiles();
        /** @var MetaHelper $meta */
        $meta = $this->getHelper('meta');

        if (null !== ($metaExcludePaths = $config->get('meta-exclude'))) {
            $allFiles = $meta->filterFilesList($allFiles, (array) $metaExcludePaths);
        }

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

            $confirmed = $this->getHelper('question')->ask(
                $input,
                $output,
                new ConfirmationQuestion('<question>Do you want to continue?</question> (y/n) ', true)
            );

            if (!$confirmed) {
                continue;
            }

            $metaClass = $meta->getMetaClass($type);

            foreach ($files as $file) {
                $fileContent = file_get_contents($file);

                if ($meta->isUpdatable($metaClass, $fileContent)) {
                    if (false === $dryRun) {
                        file_put_contents($file, $meta->updateContent($metaClass, $header, $fileContent));
                    }

                    $output->writeln(
                        sprintf(
                            '%s<info>Updated header in file "%s"</info>',
                            $dryRun === false ? '' : ' <comment>[DRY-RUN] </comment>',
                            $file
                        )
                    );
                } else {
                    $output->writeln(
                        sprintf('<info>Skipping file "%s"</info>', $file)
                    );
                }
            }
        }

        return self::COMMAND_SUCCESS;
    }
}
