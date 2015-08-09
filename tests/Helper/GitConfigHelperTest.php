<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\GitConfigHelper;
use Gush\Helper\ProcessHelper;

final class GitConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processHelper;

    /**
     * @var GitConfigHelper
     */
    private $gitConfigHelper;

    public function setUp()
    {
        $this->processHelper = $this->getMock('Gush\Helper\ProcessHelper');
        $this->gitConfigHelper = new GitConfigHelper(
            $this->processHelper,
            $this->getMockBuilder('Gush\Application')->disableOriginalConstructor()->getMock()
        );
    }

    /**
     * @test
     * @dataProvider repoUrlProvider
     */
    public function gets_information_about_the_remote($url, array $expectedInfo)
    {
        $this->processHelper
            ->expects($this->atLeastOnce())
            ->method('runCommand')
            ->with($this->equalTo('git config --local --get remote.origin.url'))
            ->will($this->returnValue($url))
        ;

        $this->assertEquals($expectedInfo, $this->gitConfigHelper->getRemoteInfo('origin'));
    }

    public function repoUrlProvider()
    {
        return [
            [
                'https://github.com/gushphp/gush',
                ['host' => 'github.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://github.com/gushphp/gush.git',
                ['host' => 'github.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://github.com/gushphp/gush-gitlab.git',
                ['host' => 'github.com', 'vendor' => 'gushphp', 'repo' => 'gush-gitlab'],
            ],
            [
                'git@github.com:gushphp/gush.git',
                ['host' => 'github.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'ssh://git@github.com:gushphp/gush.git',
                ['host' => 'github.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'git://github.com/gushphp/gush.git',
                ['host' => 'github.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'git@bitbucket.com:gushphp/gush.git',
                ['host' => 'bitbucket.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://bitbucket.com/gushphp/gush.git',
                ['host' => 'bitbucket.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://bitbucket.com/gushphp/gush',
                ['host' => 'bitbucket.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'git@gitlab.com:gushphp/gush.git',
                ['host' => 'gitlab.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://gitlab.com/gushphp/gush.git',
                ['host' => 'gitlab.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://gitlab.com/gushphp/gush',
                ['host' => 'gitlab.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'git@enterprise.github.com:gushphp/gush.git',
                ['host' => 'enterprise.github.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://enterprise.github.com/gushphp/gush.git',
                ['host' => 'enterprise.github.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://enterprise.github.com/gushphp/gush',
                ['host' => 'enterprise.github.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://private.org.com/git/gushphp/gush',
                ['host' => 'private.org.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://user@private.org.com/git/gushphp/gush',
                ['host' => 'private.org.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://user@private.org.com:8080/git/gushphp/gush',
                ['host' => 'private.org.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'https://user:pass@private.org.com:8080/git/gushphp/gush',
                ['host' => 'private.org.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'user@private.org.com:some-dir/gushphp/gush.git',
                ['host' => 'private.org.com', 'vendor' => 'gushphp', 'repo' => 'gush'],
            ],
            [
                'user@private.org.com:some-dir/gushphp/gush.gitlab.git',
                ['host' => 'private.org.com', 'vendor' => 'gushphp', 'repo' => 'gush.gitlab'],
            ],
        ];
    }
}
