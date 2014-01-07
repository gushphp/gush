<?php

namespace Gush\Test\HttpClient;

use Guzzle\Http\Message\Response;

class ResponseStub
{
    protected $client;
    protected $returnData = array();

    public function __construct(TestHttpClient $client)
    {
        $this->client = $client;
    }

    public function thenReturn(array $returnData)
    {
        $this->returnData = $returnData;
    }

    public function getResponse()
    {
        $response = new Response(200, array(), json_encode($this->returnData));

        return $response;
    }
}
