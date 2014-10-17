<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Tester;

use Gush\Tester\HttpClient\TestHttpClient;

class TestHttpClientTest extends \PHPUnit_Framework_TestCase
{
    protected $testHttpClient;

    public function setUp()
    {
        $this->testHttpClient = new TestHttpClient();
    }

    public function provideRequestMethod()
    {
        return [
            ['get'],
            ['put'],
            ['delete'],
            ['post'],
            ['patch'],
        ];
    }

    /**
     * @test
     * @dataProvider provideRequestMethod
     */
    public function uses_requests_method($method, $args = [])
    {
        $args = array_merge([
            'path' => '/foo/bar',
            'body' => null,
            'parameters' => [],
            'headers' => [],
            'body' => 'this is body',
        ], $args);

        $expectedData = [
            'this' => 'is',
            'some' => 'data',
        ];

        $whenMethod = sprintf('when%s', ucfirst($method));

        switch ($method) {
            case 'get':
                $stub = $this->testHttpClient->$whenMethod($args['path'], $args['parameters'], $args['headers']);
                break;
            default:
                $stub = $this->testHttpClient->$whenMethod($args['path'], $args['body'], $args['headers']);
                break;
        }

        $stub->thenReturn($expectedData);

        switch ($method) {
            case 'get':
                $res = $this->testHttpClient->$method($args['path'], $args['parameters'], $args['headers']);
                break;
            default:
                $res = $this->testHttpClient->$method($args['path'], $args['body'], $args['headers']);
                break;
        }

        $this->assertEquals(json_encode($expectedData), $res->getBody());
        $this->assertEquals($args['headers'], $res->getHeaderLines());
    }
}
