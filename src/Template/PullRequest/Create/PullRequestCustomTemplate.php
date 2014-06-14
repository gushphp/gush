<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\PullRequest\Create;

use Gush\Application;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class PullRequestCustomTemplate extends AbstractSymfonyTemplate
{
    /**
     * @var \Gush\Application
     */
    private $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException when fields structure is invalid
     */
    public function getRequirements()
    {
        $fields = $this->application->getConfig()->get('table-pr') ?: [];
        $fields['description'] = ['Description', ''];

        if (count($fields) < 2) {
            throw new \RuntimeException(
                'table-pr structure requires at least one row, please check your local .gush.yml'
            );
        }

        foreach ($fields as $name => $rowData) {
            if (!is_string($name)) {
                throw new \RuntimeException(
                    'table-pr table row-name must be a string, please check your local .gush.yml'
                );
            }

            if (!is_array($rowData) || count($rowData) <> 2) {
                throw new \RuntimeException(
                    sprintf(
                        'table-pr table row-data "%s" must be an array with exactly two values like: '.
                        '[Label, default value].'.PHP_EOL.'please check your local .gush.yml',
                        $name
                    )
                );
            }
        }

        return $fields;
    }

    public function getName()
    {
        return 'pull-request-create/custom';
    }
}
