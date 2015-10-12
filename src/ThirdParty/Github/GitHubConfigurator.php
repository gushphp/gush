<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This file is part of Gush.
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GitHubConfigurator extends DefaultConfigurator
{
    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $config = parent::interact($input, $output);

        // Do authentication now so we can detect 2fa
        if (self::AUTH_HTTP_PASSWORD === $config['authentication']['http-auth-type']) {
            $client = new Client();

            try {
                $client->authenticate(
                    $config['authentication']['username'],
                    $config['authentication']['password-or-token']
                );

                try {
                    // Make a call to test authentication
                    $client->api('authorizations')->all();
                } catch (TwoFactorAuthenticationRequiredException $e) {
                    // Create a random authorization to make GitHub send the code
                    // We expect an exception, which gets cached by the next catch-block
                    // Note. This authorization is not actually created
                    $client->api('authorizations')->create(
                        [
                            'note' => 'Gush on '.gethostname().mt_rand(),
                            'scopes' => ['public_repo'],
                        ]
                    );
                }
            } catch (TwoFactorAuthenticationRequiredException $e) {
                $isAuthenticated = false;
                $authenticationAttempts = 0;
                $authorization = [];

                $scopes = [
                    'user',
                    'user:email',
                    'public_repo',
                    'repo',
                    'repo:status',
                    'read:org',
                ];

                $output->writeln(
                    sprintf('Two factor authentication of type %s is required: ', trim($e->getType()))
                );

                // We already know the password is valid, we just need a valid code
                // Don't want fill in everything again when you know its valid ;)
                while (!$isAuthenticated) {
                    // Prevent endless loop with a broken test
                    if ($authenticationAttempts > 500) {
                        $output->writeln('<error>To many attempts, aborting.</error>');

                        break;
                    }

                    if ($authenticationAttempts > 0) {
                        $output->writeln('<error>Authentication failed please try again.</error>');
                    }

                    try {
                        $code = $this->questionHelper->ask(
                            $input,
                            $output,
                            (new Question('Authentication code: '))->setValidator([$this, 'validateNoneEmpty'])
                        );

                        // Use a date with time to make sure its unique
                        // Its not possible get existing authorizations, only a new one
                        $time = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s');
                        $authorization = $client->api('authorizations')->create(
                            [
                                'note' => sprintf('Gush on %s at %s', gethostname(), $time),
                                'scopes' => $scopes,
                            ],
                            $code
                        );

                        $isAuthenticated = isset($authorization['token']);
                    } catch (TwoFactorAuthenticationRequiredException $e) {
                        // Noop, continue the loop, try again
                    } catch (\Exception $e) {
                        $output->writeln("<error>{$e->getMessage()}</error>");
                        $output->writeln('');
                    }

                    ++$authenticationAttempts;
                }

                if ($isAuthenticated) {
                    $config['authentication']['http-auth-type'] = self::AUTH_HTTP_TOKEN;
                    $config['authentication']['password-or-token'] = $authorization['token'];
                }
            }
        }

        return $config;
    }
}
