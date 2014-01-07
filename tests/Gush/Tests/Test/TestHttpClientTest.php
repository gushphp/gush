<?php

/*
 * This file is part of the Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Test;

use Gush\Test\HttpClient\TestHttpClient;

/**
 * @author Daniel T Leech <dantleech@gmail.com>
 */
class TestHttpClientTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->testHttpClient = new TestHttpClient();
    }

    public function provideRequestMethod()
    {
        return array(
            array('get'),
            array('put'),
            array('delete'),
            array('post'),
            array('patch'),
        );
    }

    /**
     * @dataProvider provideRequestMethod
     */
    public function testRequestMethod($method, $args = array())
    {
        $args = array_merge(array(
            'path' => '/foo/bar',
            'body' => null,
            'parameters' => array(),
            'headers' => array(),
            'body' => 'this is body',
        ), $args);

        $expectedData = array(
            'this' => 'is',
            'some' => 'data',
        );

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

        $this->assertEquals(json_encode($expectedData, true), $res->getBody());
        $this->assertEquals($args['headers'], $res->getHeaderLines());
    }
}
