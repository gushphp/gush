<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tester\HttpClient;

use Guzzle\Http\Message\Response;

class ResponseStub
{
    protected $client;
    protected $returnData = [];

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
        return new Response(200, [], json_encode($this->returnData));
    }
}
