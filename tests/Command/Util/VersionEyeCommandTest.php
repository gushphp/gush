<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Util;

use Gush\Command\Util\VersionEyeCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class VersionEyeCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function runs_version_eye_command()
    {
        $processHelper = $this->expectProcessHelper();
        $tester = $this->getCommandTester($command = new VersionEyeCommand());
        $command->getHelperSet()->set($processHelper, 'process');
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush-sandbox'], ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertEquals(OutputFixtures::PULL_REQUEST_VERSIONEYE, $res);
    }

    private function expectProcessHelper()
    {
        $processHelper = $this->getMock(
            'Gush\Helper\ProcessHelper',
            ['runCommands']
        );
        $processHelper->expects($this->at(0))
            ->method('runCommands')
            ->with(
                [
                    [
                        'line' => 'composer require symfony/console 2.4.3 --no-update',
                        'allow_failures' => true,
                    ],
                ]
            )
        ;
        $processHelper->expects($this->at(1))
            ->method('runCommands')
            ->with(
                [
                    [
                        'line' => 'composer require symfony/process 2.4.3 --no-update',
                        'allow_failures' => true,
                    ],
                ]
            )
        ;

        return $processHelper;
    }

    protected function buildVersionEyeClient()
    {
        $request = $this->getMockBuilder('Guzzle\Http\Message\RequestInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $client->expects($this->at(0))
            ->method('get')
            ->with('/api/v2/projects')
            ->will($this->returnValue($request))
        ;
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($this->firstReturn()))
        ;
        $request->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response))
        ;

        $lastRequest = $this->getMockBuilder('Guzzle\Http\Message\RequestInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $lastResponse = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $lastResponse->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($this->secondReturn()))
        ;
        $lastRequest->expects($this->once())
            ->method('send')
            ->will($this->returnValue($lastResponse))
        ;
        $client->expects($this->at(1))
            ->method('get')
            ->with('/api/v2/projects/5301b708ec1375aa7f0000ad')
            ->will($this->returnValue($lastRequest))
        ;

        return $client;
    }

    private function firstReturn()
    {
        return <<<EOT
[{"id":"52f57f54ec1375d0a600013c","project_key":"maven2_gush_1","name":"gush","project_type":"Maven2","private":false,"period":"weekly","source":"url","dep_number":null,"out_number":0,"created_at":"2014-02-08T00:50:28Z","updated_at":"2014-02-08T00:50:28Z"},{"id":"52f57f5aec1375fd0b0000b4","project_key":"maven2_gush_git_1","name":"gush.git","project_type":"Maven2","private":false,"period":"weekly","source":"url","dep_number":null,"out_number":0,"created_at":"2014-02-08T00:50:34Z","updated_at":"2014-02-08T00:50:34Z"},{"id":"52f57f71ec1375fd0b0000b6","project_key":"composer_vespolina_action_1","name":"vespolina/action","project_type":"composer","private":false,"period":"weekly","source":"url","dep_number":4,"out_number":0,"created_at":"2014-02-08T00:50:58Z","updated_at":"2014-02-08T00:50:58Z"},{"id":"52f580fbec137591740000a8","project_key":"composer_cordoval_gush_sandbox_1","name":"cordoval/gush-sandbox","project_type":"composer","private":false,"period":"weekly","source":"url","dep_number":3,"out_number":2,"created_at":"2014-02-08T00:57:31Z","updated_at":"2014-02-08T00:58:48Z"},{"id":"52fd77ddec1375edd50003ca","project_key":"maven2_gush_3","name":"gush","project_type":"Maven2","private":false,"period":"weekly","source":"url","dep_number":null,"out_number":0,"created_at":"2014-02-14T01:56:45Z","updated_at":"2014-02-14T01:56:45Z"},{"id":"52fd77f2ec1375edd50003cc","project_key":"composer_gushphp_gush_1","name":"gushphp/gush","project_type":"composer","private":false,"period":"weekly","source":"url","dep_number":14,"out_number":3,"created_at":"2014-02-14T01:57:13Z","updated_at":"2014-04-07T13:51:42Z"},{"id":"5301b071ec1375aa7f0000a9","project_key":"composer_cordoval_gush_sandbox_2","name":"cordoval/gush-sandbox","project_type":"composer","private":false,"period":"weekly","source":"url","dep_number":3,"out_number":2,"created_at":"2014-02-17T06:47:14Z","updated_at":"2014-02-17T06:47:14Z"},{"id":"5301b6e0ec1375bab10003c5","project_key":"composer_cordoval_gush_sandbox_3","name":"cordoval/gush-sandbox","project_type":"composer","private":false,"period":"weekly","source":"url","dep_number":3,"out_number":2,"created_at":"2014-02-17T07:14:40Z","updated_at":"2014-02-17T07:14:40Z"},{"id":"5301b708ec1375aa7f0000ad","project_key":"composer_gush_gush_sandbox_1","name":"gushphp/gush-sandbox","project_type":"composer","private":false,"period":"weekly","source":"url","dep_number":3,"out_number":2,"created_at":"2014-02-17T07:15:20Z","updated_at":"2014-02-17T07:21:27Z"}]
EOT;
    }

    private function secondReturn()
    {
        return <<<EOT
{"id":"5301b708ec1375aa7f0000ad","project_key":"composer_gush_gush_sandbox_1","name":"gushphp/gush-sandbox","project_type":"composer","private":false,"period":"weekly","source":"url","dep_number":3,"out_number":2,"created_at":"2014-02-17T07:15:20Z","updated_at":"2014-02-17T07:21:27Z","dependencies":[{"name":"symfony/console","prod_key":"symfony/console","group_id":null,"artifact_id":null,"license":"MIT","version_current":"2.4.3","version_requested":"2.0","comparator":"=","unknown":false,"outdated":true,"stable":true},{"name":"symfony/process","prod_key":"symfony/process","group_id":null,"artifact_id":null,"license":"MIT","version_current":"2.4.3","version_requested":"2.0","comparator":"=","unknown":false,"outdated":true,"stable":true},{"name":"php","prod_key":"php","group_id":null,"artifact_id":null,"license":"unknown","version_current":"5.5.11","version_requested":"5.5.11","comparator":">=","unknown":false,"outdated":false,"stable":true}]}
EOT;
    }
}
