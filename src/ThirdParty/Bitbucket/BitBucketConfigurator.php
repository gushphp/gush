<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * BitBucketConfigurator is the Configurator class for BitBucket configuring.
 *
 * Overwriting because OAuth requires a secret.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitBucketConfigurator extends DefaultConfigurator
{
    /**
     * Constructor.
     *
     * @param QuestionHelper $questionHelper  QuestionHelper instance
     * @param string         $label   Label of the Configurator (eg. BitBucket or BitBucket IssueTracker)
     * @param string         $apiUrl  Default URL to API service (eg. 'https://api.bitbucket.org')
     * @param string         $repoUrl Default URL to repository (eg. 'https://bitbucket.org')
     */
    public function __construct(QuestionHelper $questionHelper, $label, $apiUrl, $repoUrl)
    {
        $this->questionHelper = $questionHelper;
        $this->label = $label;
        $this->apiUrl = $apiUrl;
        $this->repoUrl = $repoUrl;

        $authenticationOptions = [
            0 => ['Password', self::AUTH_HTTP_PASSWORD],
            1 => ['OAuth', self::AUTH_HTTP_TOKEN]
        ];

        $this->authenticationOptions = $authenticationOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $config = [];

        $authenticationLabels = array_map(
            function ($value) {
                return $value[0];
            },
            $this->authenticationOptions
        );

        $authenticationType = array_search(
            $this->questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Choose '.$this->label.' authentication type:',
                    $authenticationLabels,
                    $authenticationLabels[0]
                )
            ),
            $authenticationLabels
        );

        $config['authentication'] = [];
        $config['authentication']['http-auth-type'] = $this->authenticationOptions[$authenticationType][1];

        $config['authentication']['username'] = $this->questionHelper->ask(
            $input,
            $output,
            (new Question('Username: '))->setValidator([$this, 'validateNoneEmpty'])
        );

        if (static::AUTH_HTTP_TOKEN === $config['authentication']['http-auth-type']) {
            $config['authentication']['key'] = $this->questionHelper->ask(
                $input,
                $output,
                (new Question('Key: '))->setValidator([$this, 'validateNoneEmpty'])
            );

            $config['authentication']['secret'] = $this->questionHelper->ask(
                $input,
                $output,
                (new Question('Secret: '))
                    ->setHidden(true)
                    ->setValidator([$this, 'validateNoneEmpty'])
            );
        } else {
            $config['authentication']['password'] = $this->questionHelper->ask(
                $input,
                $output,
                (new Question('Password: '))
                    ->setValidator([$this, 'validateNoneEmpty'])
                    ->setHidden(true)
            );
        }

        // Not really configurable at the moment, so hard-configured
        $config['base_url'] = rtrim($this->apiUrl, '/');
        $config['repo_domain_url'] = rtrim($this->repoUrl, '/');

        return $config;
    }
}
