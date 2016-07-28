<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Github;

use Github\Client;
use Github\Exception\TwoFactorAuthenticationRequiredException;
use Gush\Adapter\DefaultConfigurator;
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GitHubConfigurator extends DefaultConfigurator
{
    /**
     * @var StyleHelper
     */
    private $styleHelper;

    public function __construct(StyleHelper $styleHelper, $label, $apiUrl, $repoUrl)
    {
        $this->styleHelper = $styleHelper;
        $this->label = $label;
        $this->apiUrl = $apiUrl;
        $this->repoUrl = $repoUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $oAuthToken = null;

        $client = new Client();

        $authenticationAttempts = 0;
        $authentication = [
            'username' => null,
            'password' => null,
            'base_url' => $this->apiUrl,
            'repo_domain_url' => $this->repoUrl,
        ];

        while (null === $oAuthToken) {
            if ($authenticationAttempts > 0) {
                $this->styleHelper->error('Authentication failed please try again.');
            }

            $authentication['base_url'] = $this->styleHelper->ask(
                sprintf('%s API url', $this->label),
                $authentication['base_url'],
                [$this, 'validateUrl']
            );

            $authentication['repo_domain_url'] = $this->styleHelper->ask(
                sprintf('%s web url', $this->label),
                $authentication['repo_domain_url'],
                [$this, 'validateUrl']
            );

            $this->styleHelper->note(
                [
                    'For security reasons an authentication token will be stored instead of your password.',
                    sprintf(
                        'To revoke access of this token you can visit %s/settings/tokens',
                        $authentication['repo_domain_url']
                    ),
                ]
            );

            $authentication['username'] = $this->styleHelper->ask(
                'Username',
                $authentication['username'],
                [$this, 'validateNoneEmpty']
            );

            $authentication['password'] = $this->styleHelper->askHidden(
                'Password',
                [$this, 'validateNoneEmpty']
            );

            try {
                $client->authenticate(
                    $authentication['username'],
                    $authentication['password']
                );

                $oAuthToken = $this->createAuthorization($client)['token'];
            } catch (TwoFactorAuthenticationRequiredException $e) {
                $oAuthToken = $this->handle2fa($client, $e)['token'];
            }
        }

        return $this->getConfigStructure(
            $authentication['username'],
            $oAuthToken,
            $authentication['base_url'],
            $authentication['repo_domain_url']
        );
    }

    private function getConfigStructure($username, $token, $apiUrl, $repoUrl)
    {
        $config = [
            'base_url' => $apiUrl,
            'repo_domain_url' => $repoUrl,
        ];

        $config['authentication'] = [
            'username' => $username,
            'token' => $token,
        ];

        return $config;
    }

    private function handle2fa(Client $client, TwoFactorAuthenticationRequiredException $e)
    {
        $authenticationAttempts = 0;
        $authorization = [];
        $type = trim($e->getType()); // Stupid API gives text with spaces

        $message = [
            'Username and password were correct.',
            'Two factor authentication is required to continue authentication.',
        ];

        if ('app' === $type) {
            $message[] = 'Open the two-factor authentication app on your device to view your authentication code and verify your identity.';
        } elseif ('sms' === $type) {
            $message[] = 'You have been sent an SMS message with an authentication code to verify your identity.';
        }

        $this->styleHelper->note($message);

        // We already know the password is valid, we just need a valid code
        // Don't want to fill in everything again when you know it's valid ;)
        while (!isset($authorization['token'])) {
            if ($authenticationAttempts > 0) {
                $this->styleHelper->error('Two factor authentication code was invalid, please try again.');
            }

            try {
                $code = $this->styleHelper->ask('Two factor authentication code', null, [$this, 'validateNoneEmpty']);

                $authorization = $this->createAuthorization($client, $code);
            } catch (TwoFactorAuthenticationRequiredException $e) {
                // No-op, continue the loop, try again
            } catch (\Exception $e) {
                $this->styleHelper->error($e->getMessage());
                $this->styleHelper->newLine();
            }

            ++$authenticationAttempts;
        }

        return $authorization;
    }

    private function createAuthorization(Client $client, $code = null)
    {
        $scopes = [
            'user:email',
            'repo',
            'repo:status',
            'read:org',
        ];

        // Use a date with time to make sure the name is unique.
        // It's not possible to get existing authorizations, only to create new ones.
        $time = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s \U\T\C');

        $authorization = $client->api('authorizations')->create(
            [
                'note' => sprintf('Gush on %s at %s', gethostname(), $time),
                'scopes' => $scopes,
            ],
            $code
        );

        // NB. This message will be only shown when eg. fa2 is disabled or the 2fa code was correct.
        // Else the create() in authorizations will throw an exception.
        $this->styleHelper->success('Successfully authenticated, token note: '.$authorization['note']);

        return $authorization;
    }
}
