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

use Gush\Helper\FilesystemHelper;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Gush\Helper\ProcessHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class GitHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GitHelper
     */
    private $git;

    /**
     * @var GitHelper
     */
    private $unitGit;

    /**
     * @var ProcessHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processHelper;

    /**
     * @var GitConfigHelper|ObjectProphecy
     */
    private $gitConfigHelper;

    /**
     * @var FilesystemHelper|ObjectProphecy
     */
    private $filesystemHelper;

    /**
     * @var FilesystemHelper
     */
    private $realFsHelper;

    public function setUp()
    {
        $this->processHelper = $this->getMock('Gush\Helper\ProcessHelper');

        $this->filesystemHelper = $this->prophesize('Gush\Helper\FilesystemHelper');
        $this->filesystemHelper->getName()->willReturn('filesystem');

        $this->gitConfigHelper = $this->prophesize('Gush\Helper\GitConfigHelper');
        $this->gitConfigHelper->setHelperSet(Argument::any());
        $this->gitConfigHelper->getName()->willReturn('git_config');

        $this->realFsHelper = new FilesystemHelper();

        $this->git = new GitHelper(new ProcessHelper(), $this->gitConfigHelper->reveal(), $this->realFsHelper);
        $this->unitGit = new GitHelper(
            $this->processHelper,
            $this->gitConfigHelper->reveal(),
            $this->filesystemHelper->reveal()
        );
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
        $sourceBranch = 'amazing-feature';
        $tmpName = $this->realFsHelper->newTempFilename();
        $hash = '8ae59958a2632018275b8db9590e9a79331030cb';
        $message = "Black-box testing 123\n\n\nAah!";

        $processHelper = $this->prophesize('Gush\Helper\ProcessHelper');
        $this->unitGit = new GitHelper(
            $processHelper->reveal(),
            $this->gitConfigHelper->reveal(),
            $this->filesystemHelper->reveal()
        );

        $this->filesystemHelper->newTempFilename()->willReturn($tmpName);

        $processHelper->runCommand('git status --porcelain --untracked-files=no')->willReturn("\n");
        $processHelper->runCommand('git rev-parse --abbrev-ref HEAD')->willReturn('master');
        $processHelper->runCommand(['git', 'checkout', 'master'])->shouldBeCalled();
        $processHelper->runCommands(
            [
                [
                    'line' => ['git', 'merge', '--no-ff', '--no-commit', '--no-log', 'amazing-feature'],
                    'allow_failures' => false,
                ],
                [
                    'line' => ['git', 'commit', '-F', $tmpName],
                    'allow_failures' => false,
                ],
            ]
        )->shouldBeCalled();

        $processHelper->runCommand('git rev-parse HEAD')->willReturn($hash);

        $this->assertEquals($hash, $this->unitGit->mergeBranch($base, $sourceBranch, $message));
    }

    /**
     * @test
     */
    public function merges_remote_branch_fast_forward_in_clean_wc()
    {
        $base = 'master';
        $sourceBranch = 'amazing-feature';
        $hash = '8ae59958a2632018275b8db9590e9a79331030cb';

        $processHelper = $this->prophesize('Gush\Helper\ProcessHelper');
        $this->unitGit = new GitHelper(
            $processHelper->reveal(),
            $this->gitConfigHelper->reveal(),
            $this->filesystemHelper->reveal()
        );

        $processHelper->runCommand('git status --porcelain --untracked-files=no')->willReturn("\n");
        $processHelper->runCommand('git rev-parse --abbrev-ref HEAD')->willReturn('master');
        $processHelper->runCommand(['git', 'checkout', 'master'])->shouldBeCalled();
        $processHelper->runCommand(['git', 'merge', '--ff', 'amazing-feature'])->shouldBeCalled();
        $processHelper->runCommand('git rev-parse HEAD')->willReturn($hash);

        $this->assertSame($hash, $this->unitGit->mergeBranch($base, $sourceBranch, null, true));
    }
}
