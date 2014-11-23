<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\FilesystemHelper;
use Gush\Helper\GitHelper;
use Gush\Helper\ProcessHelper;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

class GitHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GitHelper
     */
    protected $git;

    /**
     * @var GitHelper
     */
    protected $unitGit;

    /**
     * @var ProcessHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processHelper;

    /**
     * @var FilesystemHelper|ObjectProphecy
     */
    private $filesystemHelper;

    /**
     * @var Prophet
     */
    private $prophet;

    /**
     * @var FilesystemHelper
     */
    private $realFsHelper;

    public function setUp()
    {
        $this->prophet = new Prophet();

        $this->processHelper = $this->getMock('Gush\Helper\ProcessHelper');

        $this->filesystemHelper = $this->prophet->prophesize('Gush\Helper\FilesystemHelper');
        $this->filesystemHelper->getName()->willReturn('filesystem');

        $this->realFsHelper = new FilesystemHelper();

        $this->git = new GitHelper(new ProcessHelper(), $this->realFsHelper);
        $this->unitGit = new GitHelper($this->processHelper, $this->filesystemHelper->reveal());
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    /**
     * @test
     */
    public function bears_the_right_helper_name()
    {
        $this->assertEquals('git', $this->git->getName());
    }

    /**
     * @test
     */
    public function gets_current_git_branch_name()
    {
        exec('git rev-parse --abbrev-ref HEAD', $output);

        if ('HEAD' === $output[0]) {
            $this->markTestSkipped('Unable to run this test in a detached HEAD state.');
        }

        $this->assertEquals($output[0], $this->git->getActiveBranchName());
    }

    /**
     * @test
     */
    public function gets_the_last_tag_on_current_branch()
    {
        exec('git describe --tags --abbrev=0 HEAD', $output);
        $this->assertEquals($output[0], $this->git->getLastTagOnBranch());
    }

    /**
     * @test
     * @dataProvider repoUrlProvider
     */
    public function gets_the_repository_name($repo)
    {
        $return = <<<EOT
* remote origin
  Fetch URL: {$repo}
  Push  URL: {$repo}
  HEAD branch: (not queried)
  Remote branches: (status not queried)
    master
  Local branches configured for 'git pull':
    master                             merges with remote master
  Local ref configured for 'git push' (status not queried):
    (matching) pushes to (matching)
EOT;

        $this->processHelper
            ->expects($this->any())
            ->method('runCommand')
            ->will($this->returnValue($return))
        ;

        $this->assertEquals('gush', $this->unitGit->getRepoName());
    }

    /**
     * @test
     * @dataProvider repoUrlProvider
     */
    public function gets_vendor_name_for_repository($repo)
    {
        $return = <<<EOT
* remote origin
  Fetch URL: {$repo}
  Push  URL: {$repo}
  HEAD branch: (not queried)
  Remote branches: (status not queried)
    master
  Local branches configured for 'git pull':
    master                             merges with remote master
  Local ref configured for 'git push' (status not queried):
    (matching) pushes to (matching)
EOT;

        $this->processHelper
            ->expects($this->any())
            ->method('runCommand')
            ->will($this->returnValue($return))
        ;

        $this->assertEquals(getenv('GIT_VENDOR_NAME'), $this->unitGit->getVendorName());
    }

    /**
     * @test
     */
    public function lists_files()
    {
        // Smoke test for a real listFiles
        $res = $this->git->listFiles();
        $this->assertGreaterThan(50, $res);
    }

    /**
     * @test
     */
    public function merges_remote_branch_in_clean_wc()
    {
        $base = 'master';
        $baseRemote = $sourceRemote = 'origin';
        $sourceBranch = 'amazing-feature';
        $tmpName = $this->realFsHelper->newTempFilename();
        $hash = '8ae59958a2632018275b8db9590e9a79331030cb';
        $message = "Black-box testing 123\n\n\nAah!";

        $processHelper = $this->prophet->prophesize('Gush\Helper\ProcessHelper');
        $this->unitGit = new GitHelper($processHelper->reveal(), $this->filesystemHelper->reveal());

        $this->filesystemHelper->newTempFilename()->willReturn($tmpName);

        $processHelper->runCommand('git config --local --get remote.origin.url', true)->willReturn(true);
        $processHelper->runCommand('git status --porcelain --untracked-files=no')->willReturn("\n");
        $processHelper->runCommand('git rev-parse --abbrev-ref HEAD')->willReturn("master");
        $processHelper->runCommand(['git', 'checkout', 'master'])->shouldBeCalled();
        $processHelper->runCommands(
            [
                [
                    'line' => 'git remote update',
                    'allow_failures' => false,
                ],
                [
                    'line' => 'git checkout '.$base,
                    'allow_failures' => false,
                ],
                [
                    'line' => 'git pull --ff-only',
                    'allow_failures' => false,
                ],
                [
                    'line' => ['git', 'merge', '--no-ff', '--no-commit', $sourceRemote.'/'.$sourceBranch],
                    'allow_failures' => false,
                ],
                [
                    'line' => ['git', 'commit', '-F', $tmpName],
                    'allow_failures' => false,
                ],
            ]
        )->shouldBeCalled();

        $processHelper->runCommand('git rev-parse HEAD')->willReturn($hash);
        $processHelper->runCommand(['git', 'push', $baseRemote])->shouldBeCalled();

        $this->assertEquals(
            $hash,
            $this->unitGit->mergeRemoteBranch(
                $sourceRemote,
                $baseRemote,
                $base,
                $sourceBranch,
                $this->realFsHelper,
                $message
            )
        );
    }

    public function repoUrlProvider()
    {
        return [
            ['https://github.com/gushphp/gush'],
            ['https://github.com/gushphp/gush.git'],
            ['git@github.com:gushphp/gush.git'],
            ['git@bitbucket.com:gushphp/gush.git'],
            ['https://bitbucket.com/gushphp/gush.git'],
            ['https://bitbucket.com/gushphp/gush'],
            ['git@gitlab.com:gushphp/gush.git'],
            ['https://gitlab.com/gushphp/gush.git'],
            ['https://gitlab.com/gushphp/gush'],
            ['git@entperprise.github.com:gushphp/gush.git'],
            ['https://entperprise.github.com/gushphp/gush.git'],
            ['https://entperprise.github.com/gushphp/gush'],
        ];
    }
}
