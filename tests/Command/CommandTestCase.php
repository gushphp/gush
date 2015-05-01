<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Config;
use Gush\Tests\BaseTestCase;
use Prophecy\Prophet;
use Symfony\Component\Console\Command\Command;

class CommandTestCase extends BaseTestCase
{
    protected static $localConfig = [
        'repo_adapter' => 'github_enterprise',
        'issue_adapter' => 'github_enterprise',
        'repo_org' => 'gushphp',
        'repo_name' => 'gush',
        'remove-promote' => true,
    ];

    protected function assertCommandOutputMatches($expectedLines, $output, $regex = false)
    {
        $output = trim($output);
        $expectedLines = (array) $expectedLines;

        foreach ($expectedLines as $matchLine) {
            if (is_array($matchLine)) {
                $line = $matchLine[0];
                $lineRegex = $matchLine[1];
            } else {
                $line = $matchLine;
                $lineRegex = $regex;
            }

            if (!$lineRegex) {
                $line = preg_replace('#\s+#', '\\s+', preg_quote($line, '#'));
            }

            $this->assertRegExp('#'.$line.'#m', $output);
        }
    }

    protected function assertTableOutputMatches(array $header, $rows, $output)
    {
        $output = trim($output);

        $headerRegex = '';

        foreach ($header as $item) {
            $headerRegex .= '(\s+|(\s*\|)\s*)'.preg_quote($item, '#');
        }

        $headerRegex .= '(\s+|(\s*\|)\s*)';

        $this->assertRegExp('#'.$headerRegex.'#m', $output, 'Expected header to be found');

        foreach ($rows as $columns) {
            $columnsRegex = '';

            foreach ($columns as $column) {
                $columnsRegex .= '(\s+|(\s*\|)\s*)'.preg_quote($column, '#');
            }

            $columnsRegex .= '(\s+|(\s*\|)\s*)';

            $this->assertRegExp('#'.$columnsRegex.'#m', $output, 'Expected columns to be found.');
        }
    }

    /**
     * @param Command $command
     *
     * @param array|null $systemConfig
     * @param array|null $localConfig
     * @param \Closure|null $helperSetManipulator
     *
     * @return CommandTester
     */
    protected function getCommandTester(
        Command $command,
        array $systemConfig = null,
        array $localConfig = null,
        $helperSetManipulator = null
    ) {
        if (null === $systemConfig) {
            $systemConfig = [
                'adapters' => [
                    'github_enterprise' => [
                        'authentication' => [
                            'username' => 'cordoval',
                            'password' => 'very-un-secret',
                        ]
                    ]
                ]
            ];
        }

        if (null === $localConfig) {
            $localConfig = self::$localConfig;
        }

        if ($this->requiresRealConfigDir()) {
            $config = new Config(
                $this->getNewTmpFolder('home'),
                $this->getNewTmpFolder('cache'),
                $systemConfig,
                $this->getNewTmpFolder('repo-dir'),
                $localConfig
            );
        } else {
            try {
                // Note. The paths must be invalid to always trigger the exception
                $config = new Config(':?/temp/user', ':?/temp/gush', $systemConfig, ':?/temp/repo-dir', $localConfig);
            } catch (IOException $e) {
                echo sprintf(
                    "Test-class \"%s\" seems to use the filesystem! \nOverwrite requiresRealConfigDir() with 'return ".
                    "true;' to enable the Configuration filesystem usage.",
                    get_class($this)
                );

                throw $e;
            }
        }

        $application = $this->getApplication($config, $helperSetManipulator);
        $command->setApplication($application);

        return new CommandTester($command);
    }

    /**
     * @param Command      $command
     * @param array|string $input
     */
    protected function setExpectedCommandInput(Command $command, $input)
    {
        if (is_array($input)) {
            $input = implode("\n", $input);
        }

        $helper = $command->getHelper('gush_question');
        $helper->setInputStream($this->getInputStream($input));
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }

    /**
     * Overwrite the method to enable configuration
     * saving.
     *
     * This should only be done when required by test
     * as this slows down the testing process.
     *
     * @return bool return true to use a real dir rather a dummy
     *              This required for configuration storing commands.
     */
    protected function requiresRealConfigDir()
    {
        return false;
    }
}
