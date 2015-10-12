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

namespace Gush\ThirdParty\Bitbucket\Listener;

use Bitbucket\API\Http\Listener\ListenerInterface;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Gush\Exception\AdapterException;

class ErrorListener implements ListenerInterface
{
    private $disabled = false;

    public function disableListener($permanent = false)
    {
        $this->disabled = $permanent ? true : 1;
    }

    public function enableListener()
    {
        $this->disabled = false;
    }

    public function preSend(RequestInterface $request)
    {
        // noop
    }

    public function postSend(RequestInterface $request, MessageInterface $response)
    {
        if ($this->disabled) {
            if (1 === $this->disabled) {
                $this->disabled = false;
            }

            return;
        }

        if (!$response->isSuccessful()) {
            $resultArray = json_decode($response->getContent(), true);

            if (isset($resultArray['error'])) {
                $errorMessage = $resultArray['error']['message'];
            } else {
                $errorMessage = [];
                $errorMessage[] = 'No message found. If you think this is a bug please report it to the Gush team.';
                $errorMessage[] = 'WARNING! The Request contains confidential information such as password or token.';
                $errorMessage[] = 'Raw request: '.(string) $request.PHP_EOL.PHP_EOL;
                $errorMessage[] = 'Raw response: '.(string) $response;

                $errorMessage = implode(PHP_EOL, $errorMessage);
            }

            throw new AdapterException($errorMessage);
        }
    }

    public function getName()
    {
        return 'error';
    }
}
