<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
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
 * DefaultConfigurator is the default Configurator class for Adapter configuring.
 */
class DefaultConfigurator implements Configurator
{
    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    protected $questionHelper;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * @var string
     */
    protected $repoUrl;

    /**
     * @var string[]
     */
    protected $authenticationOptions = [];

    /**
     * Constructor.
     *
     * @param QuestionHelper $questionHelper        QuestionHelper instance
     * @param string         $label                 Label of the Configurator (eg. GitHub or GitHub IssueTracker)
     * @param string         $apiUrl                Default URL to API service (eg. 'https://api.github.com/')
     * @param string         $repoUrl               Default URL to repository (eg. 'https://github.com')
     * @param string[]       $authenticationOptions Numeric array with supported authentication options
     *                                            [idx => [label, value]]
     */
    public function __construct(QuestionHelper $questionHelper, $label, $apiUrl, $repoUrl, $authenticationOptions = [])
    {
        $this->questionHelper = $questionHelper;
        $this->label = $label;
        $this->apiUrl = $apiUrl;
        $this->repoUrl = $repoUrl;

        if ([] === $authenticationOptions) {
            $authenticationOptions = [
                0 => ['Password', self::AUTH_HTTP_PASSWORD],
                1 => ['Token', self::AUTH_HTTP_TOKEN]
            ];
        }

        $this->authenticationOptions = $authenticationOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $config = [];

        if (count($this->authenticationOptions) > 1) {
            $authenticationLabels = array_map(
                function ($value) {
                    return ucfirst($value[0]);
                },
                $this->authenticationOptions
            );

            $authenticationType = $this->questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Choose '.$this->label.' authentication type:',
                    $authenticationLabels,
                    0
                )
            );
        } else {
            $authenticationType = 0;
            $authenticationLabels = [$this->authenticationOptions[0][0]];
        }

        $config['authentication'] = [];
        $config['authentication']['http-auth-type'] = $this->authenticationOptions[$authenticationType][1];

        $config['authentication']['username'] = $this->questionHelper->ask(
            $input,
            $output,
            (new Question('Username: '))->setValidator([$this, 'validateNoneEmpty'])
        );

        $config['authentication']['password-or-token'] = $this->questionHelper->ask(
            $input,
            $output,
            (new Question($authenticationLabels[$authenticationType].': '))
                ->setValidator([$this, 'validateNoneEmpty'])
                ->setHidden(true)
        );

        $config['base_url'] = $this->dialog->askAndValidate(
            $output,
            sprintf('Enter your '.$this->label.' api url [%s]: ', $this->apiUrl),
            [$this, 'validateUrl'],
            false,
            $this->apiUrl
        );

        $config['repo_domain_url'] = $this->dialog->askAndValidate(
            $output,
            sprintf('Enter your '.$this->label.' repo url [%s]: ', $this->repoUrl),
            [$this, 'validateUrl'],
            false,
            $this->repoUrl
        );

        return $config;
    }

    /**
     * Validates if the value is none-empty.
     *
     * @param $value
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed original value when valid
     */
    public function validateNoneEmpty($value)
    {
        $value = trim($value);

        if (empty($value)) {
            throw new \InvalidArgumentException('The field cannot be empty.');
        }

        return $value;
    }

    /**
     * Validates if the value is none-empty and a valid URL.
     *
     * @param string $url
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function validateUrl($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('The field cannot be empty.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('The field requires a valid URL.');
        }

        return $url;
    }
}
