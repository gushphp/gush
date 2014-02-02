<?php

namespace Gush\Command;

use Gush\Feature\TemplateFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class MetaHeaderCommand extends BaseCommand implements TemplateFeature
{
    public function configure()
    {
        $this
            ->setName('meta:header')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command assets that headers are present
in files matching the gvein filter (*.php by default) in the current
git repository.

Note only PHP files are supported at the moment.
EOT
        )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not change anything, output files')
        ;
    }

    public function getTemplateDomain()
    {
        return 'meta-header';
    }

    public function getTemplateDefault()
    {
        return 'mit';
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        $template = $input->getOption('template');

        $header = $this->getHelper('template')->askAndRender($output, 'meta-header', $template);
        $header = $this->renderHeader($header);

        $files = $this->getHelper('git')->lsFiles();

        // only process PHP files for now
        $files = array_filter($files, function ($value) {
            if ('.php' == substr($value, -4)) {
                return true;
            }

            return false;
        });

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>The following header will be set on %d files:</info>', count($files)
        ));
        $output->writeln('');

        $output->writeln($header);

        $confirmed = $this->getHelper('dialog')->askConfirmation($output, 
            '<question>Do you want to continue?</question> (y/n) ', true);

        if (!$confirmed) {
            $output->writeln('Aborted');
            return self::COMMAND_SUCCESS;
        }

        foreach ($files as $file) {
            $handler = fopen($file, 'r');

            $newLines = array();
            $headerAdded = false;

            $replace = true;

            while ($line = fgets($handler)) {
                $trimmedLine = trim($line);

                if (false === $headerAdded) {
                    if (preg_match('&^\/\*\*?&', $trimmedLine)) {
                        $headerAdded = true;

                        while ($lLine = fgets($handler)) {
                            if (!preg_match('&^ ?\*&', $lLine)) {

                                if (true === $replace) {
                                    $newLines[] = $header;
                                    $headerAdded = true;
                                }
                                continue 2;
                            }
                        }
                    }

                    if (!in_array($trimmedLine, [ '<?php', '<?' ]) && $trimmedLine != '') {
                        $newLines[] = $header;
                        $newLines[] = $line;
                        $headerAdded = true;
                        continue;
                    }
                }

                $newLines[] = $line;
            }

            if (false === $dryRun) {
                file_put_contents($file, implode("", $newLines));
            }

            $output->writeln(sprintf('%s<info>Updating header in file "%s"</info>',
                $dryRun === false ? '' : ' <comment>[DRY-RUN] </comment>',
                $file
            ));
        }
    }

    /**
     * We only support PHP at the moment. Obviously we need
     * some strategy to use the correct commenting style when
     * other file types are supported.
     */
    protected function renderHeader($header)
    {
        $out = [ '/**' ];
        foreach (explode("\n", $header) as $line) {
            // avoid trailing spaces
            $out[] = ' *'.($line ? ' '.$line : '');
        }
        $out[] = ' */';
        $out[] = "\n";

        return implode("\n", $out);
    }
}
