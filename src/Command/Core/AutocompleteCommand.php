<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Core;

use Gush\Command\BaseCommand;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class AutocompleteCommand extends BaseCommand
{
    const AUTOCOMPLETE_SCRIPT = '.gush-autocomplete.bash';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:autocomplete')
            ->setDescription('Create file for Bash autocomplete')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> creates a script to autocomplete Gush commands in Bash:

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
        $buffer = new BufferedOutput();
        (new DescriptorHelper())->describe(
            $buffer,
            $this->getApplication(),
            ['format' => 'json']
        );

        $autocomplete = $this->getHelper('autocomplete');

        $script = $autocomplete->getAutoCompleteScript(json_decode($buffer->fetch(), true)['commands']);

        /** @var \Gush\Application $application */
        $application = $this->getApplication();
        $config = $application->getConfig();

        $scriptFile = $config->get('home').DIRECTORY_SEPARATOR.self::AUTOCOMPLETE_SCRIPT;

        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($scriptFile, $script);

        if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) {
            $output->writeln($script);
        }

        $output->writeln(
            '<info>
To enable Bash autocomplete, run the following command,
or add the following line to the ~/.bash_profile file:
            </info>'
        );

        $output->writeln(sprintf('source %s', $scriptFile));

        return self::COMMAND_SUCCESS;
    }
}
