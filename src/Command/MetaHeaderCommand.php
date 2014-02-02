<?php

namespace Gush\Command;

class MetaHeadersCommand extends BaseCommand implements TemplateFeature
{
    public function configure()
    {
        $this
            ->setName('meta:header')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command assets that headers are present
in files matching the gvein filter (*.php by default) in the current
git repository.

The ``--replace`` option can be specefied to apply a forced update (for
example for updating the copyright date).
EOT
        );
    }

    public function getTemplateDefault()
    {
        return 'MIT';
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $this->getHelper('git')->lsFiles();


        $files = array_filter($files, function ($key, $value) {
            if ('.php' == substr($value, -4)) {
                return true;
            }

            return false;
        });

        var_dump($files);die();
    }
}
