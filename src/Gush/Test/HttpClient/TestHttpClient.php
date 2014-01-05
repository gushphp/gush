<?php

namespace Gush\Test\HttpClient;

use Github\HttpClient\HttpClientInterface;

class TestHttpClient implements HttpClientInterface
{
    protected $stubs;

    private function getHash($path, $body, $httpMethod, $headers)
    {
        $hash = md5($path . $body . $httpMethod . serialize($headers));
        return $hash;
    }

    public function with($path, $body = null, $httpMethod = 'GET', array $headers = array())
    {
        $responseStub = new ResponseStub($this);
        $hash = $this->getHash($path, $body, $httpMethod, $headers);
        $this->stubs[$hash] = $responseStub;

        return $responseStub;
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, array $parameters = array(), array $headers = array())
    {
        return $this->request($path, null, 'GET', array('query' => $parameters));
    }

    /**
     * {@inheritDoc}
     */
    public function post($path, $body = null, array $headers = array())
    {
        return $this->request($path, $body, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function patch($path, $body = null, array $headers = array())
    {
        return $this->request($path, $body, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function put($path, $body, array $headers = array())
    {
        return $this->request($path, $body, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($path, $body = null, array $headers = array())
    {
        return $this->request($path, $body, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function request($path, $body = null, $httpMethod = 'GET', array $headers = array(), array $options = array())
    {
        $hash = $this->getHash($path, $body, $httpMethod, $headers);

        if (!isset($this->stubs[$hash])) {
            throw new \Exception(sprintf(
                'Could not find stub response for [%s]%s body: %s, headers: %s',
                $httpMethod, $path, $body, var_export($headers, true)
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
}
