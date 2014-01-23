<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tester\HttpClient;

use Github\HttpClient\HttpClientInterface;
use Guzzle\Http\Message\Response;

/**
 * @author Daniel T Leech <dantleech@gmail.com>
 */
class TestHttpClient implements HttpClientInterface
{
    protected $stubs;

    public function when($path, $body = null, $httpMethod = 'GET', array $headers = [])
    {
        $responseStub = new ResponseStub($this);
        $hash = $this->getHash($path, $body, $httpMethod, $headers);
        $this->stubs[$hash] = $responseStub;

        return $responseStub;
    }

    public function whenGet($path, $parameters = [], array $headers = [])
    {
        return $this->when($path, null, 'GET', array_merge($headers, ['query' => $parameters]));
    }

    public function whenPost($path, $body = null, array $headers = [])
    {
        return $this->when($path, $body, 'POST', $headers);
    }

    public function whenPatch($path, $body = null, array $headers = [])
    {
        return $this->when($path, $body, 'PATCH', $headers);
    }

    public function whenDelete($path, $body = null, array $headers = [])
    {
        return $this->when($path, $body, 'DELETE', $headers);
    }

    public function whenPut($path, $body, array $headers = [])
    {
        return $this->when($path, $body, 'PUT', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, array $parameters = [], array $headers = [])
    {
        return $this->request($path, null, 'GET', ['query' => $parameters]);
    }

    /**
     * {@inheritDoc}
     */
    public function post($path, $body = null, array $headers = [])
    {
        return $this->request($path, $body, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function patch($path, $body = null, array $headers = [])
    {
        return $this->request($path, $body, 'PATCH', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function put($path, $body, array $headers = [])
    {
        return $this->request($path, $body, 'PUT', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($path, $body = null, array $headers = [])
    {
        return $this->request($path, $body, 'DELETE', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function request($path, $body = null, $httpMethod = 'GET', array $headers = [], array $options = [])
    {
        $hash = $this->getHash($path, $body, $httpMethod, $headers);

        if (!isset($this->stubs[$hash])) {
            throw new \Exception(sprintf(
                'Could not find stub response for [%s]%s body: %s, headers: %s',
                $httpMethod,
                $path,
                $body,
                var_export($headers, true)
            ));
        }

        return $this->stubs[$hash]->getResponse();
    }

    /**
     * {@inheritDoc}
     */
    public function setOption($name, $value)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaders(array $headers)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($tokenOrLogin, $password, $authMethod)
    {
    }

    public function getLastResponse()
    {
        return new Response(200);
    }

    private function getHash($path, $body, $httpMethod, $headers)
    {
        return md5($path.$body.$httpMethod.serialize($headers));
    }
}
