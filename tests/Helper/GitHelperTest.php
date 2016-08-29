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
        $this->processHelper = $this->createMock(ProcessHelper::class);

        $this->filesystemHelper = $this->prophesize(FilesystemHelper::class);
        $this->filesystemHelper->getName()->willReturn('filesystem');

        $this->gitConfigHelper = $this->prophesize(GitConfigHelper::class);
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
    public function returns_this_project_has_git_enabled()
    {
        $this->assertTrue($this->git->isGitDir());
        $this->assertTrue($this->git->isGitDir()); // check again because of internal state
        $this->assertTrue($this->git->isGitDir(false));
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function returns_false_when_top_git_dir_was_expected()
    {
        chdir(__DIR__);

        $this->assertFalse($this->git->isGitDir());
        $this->assertTrue($this->git->isGitDir(false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testThrowExceptionForNonGitDir()
    {
        chdir(sys_get_temp_dir());

        $this->setExpectedExceptionRegExp('\RuntimeException', '#^fatal: Not a git repository \(or any of the parent directories\): \.git$#', 128);
        $this->assertFalse($this->git->isGitDir());
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
        $this->markTestSkipped('check this failing one');
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

        $processHelper = $this->prophesize(ProcessHelper::class);
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

        $processHelper = $this->prophesize(ProcessHelper::class);
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

    public function testBranchExists()
    {
        $sourceBranch = 'my-feat-10';
        $list = <<<'EOL'
  my-feat-10
EOL;

        $processHelper = $this->prophesize(ProcessHelper::class);
        $this->unitGit = new GitHelper(
            $processHelper->reveal(),
            $this->gitConfigHelper->reveal(),
            $this->filesystemHelper->reveal()
        );

        $processHelper->runCommand(['git', 'branch', '--list', $sourceBranch], true)->willReturn($list);
        $processHelper->runCommand(['git', 'branch', '--list', 'nonexistent-feat'], true)->willReturn('');

        $this->assertTrue($this->unitGit->branchExists($sourceBranch));
        $this->assertFalse($this->unitGit->branchExists('nonexistent-feat'));
    }

    public function testBranchExistsException()
    {
        $sourceBranch = 'my-feat-10';
        $list = <<<'EOL'
  my-feat-10
  my-feat-10
EOL;

        $processHelper = $this->prophesize(ProcessHelper::class);
        $this->unitGit = new GitHelper(
            $processHelper->reveal(),
            $this->gitConfigHelper->reveal(),
            $this->filesystemHelper->reveal()
        );

        $processHelper->runCommand(['git', 'branch', '--list', $sourceBranch], true)->willReturn($list);

        $this->setExpectedException('\RuntimeException', sprintf('Invalid list of local branches found while searching for "%s"', $sourceBranch));
        $this->assertTrue($this->unitGit->branchExists($sourceBranch));
    }

    public function testRemoteBranchExists()
    {
        $remote = 'phansys';
        $sourceBranch = 'my-feat-10';
        $remoteList = <<<'EOL'
8b64e4ecb74f2d2df582c0ab03a5c1ae890c43fb	HEAD
299aaa49cc5e1de3382c77d901736dcfd909d681	refs/heads/0.1
2ef49b196a50f481a4547b02817d9ee08dd8a0d4	refs/heads/0.2
d152871fd334619019df578a407b83ccb2c93ed7	refs/heads/0.3
5a7dbfd02ca80d5122827da832c579b116c29547	refs/heads/1.0
ae9c5ee4509cc5c141e5fd9d70c083936dc9a108	refs/heads/2.0
b735c63268579326c3187e7a21ea46947b12a163	refs/heads/my-feat
538b6ba0a3b9943641657c30f9eea702bbe3b6dd	refs/heads/my-feat-1
bb590a7055e6338943346f9c445f298d89fb9ce9	refs/heads/my-feat-10
144c0c98ab5d216f4b208cf702838357d3fdb811	refs/heads/my-feat-100
704a8bc7030da0dd56f17fd8c736e620c26c42ef	refs/heads/my-feat-1001
ce8bafdd18a5d1753d7a9872d3f402657873f249	refs/heads/new-my-feat-10
70cc7f22e4d937621dee3bbbb79b2913f6037137	refs/heads/my-feat-2
611fedc6cc77fd9a0c0b96eadce375cb28937204	refs/heads/my-feat-200
eafa079bb55453b44fe02b98f7420703c532b849	refs/heads/my-feat-2000
EOL;

        $processHelper = $this->prophesize(ProcessHelper::class);
        $this->unitGit = new GitHelper(
            $processHelper->reveal(),
            $this->gitConfigHelper->reveal(),
            $this->filesystemHelper->reveal()
        );

        $processHelper->runCommand(['git', 'ls-remote', $remote], true)->willReturn($remoteList);

        $this->assertTrue($this->unitGit->remoteBranchExists($remote, $sourceBranch));
        $this->assertFalse($this->unitGit->remoteBranchExists($remote, 'nonexistent-feat'));
    }

    public function testRemoteBranchExistsException()
    {
        $remote = 'phansys';
        $sourceBranch = 'my-feat-10';
        $remoteList = <<<'EOL'
8b64e4ecb74f2d2df582c0ab03a5c1ae890c43fb	HEAD
299aaa49cc5e1de3382c77d901736dcfd909d681	refs/heads/0.1
2ef49b196a50f481a4547b02817d9ee08dd8a0d4	refs/heads/0.2
d152871fd334619019df578a407b83ccb2c93ed7	refs/heads/0.3
5a7dbfd02ca80d5122827da832c579b116c29547	refs/heads/1.0
ae9c5ee4509cc5c141e5fd9d70c083936dc9a108	refs/heads/2.0
48e2a3a490abcad8e484d9189f001e9a222a48da	refs/heads/my-feat-10
b735c63268579326c3187e7a21ea46947b12a163	refs/heads/my-feat
538b6ba0a3b9943641657c30f9eea702bbe3b6dd	refs/heads/my-feat-1
bb590a7055e6338943346f9c445f298d89fb9ce9	refs/heads/my-feat-10
144c0c98ab5d216f4b208cf702838357d3fdb811	refs/heads/my-feat-100
704a8bc7030da0dd56f17fd8c736e620c26c42ef	refs/heads/my-feat-1001
ce8bafdd18a5d1753d7a9872d3f402657873f249	refs/heads/new-my-feat-10
70cc7f22e4d937621dee3bbbb79b2913f6037137	refs/heads/my-feat-2
611fedc6cc77fd9a0c0b96eadce375cb28937204	refs/heads/my-feat-200
eafa079bb55453b44fe02b98f7420703c532b849	refs/heads/my-feat-2000
EOL;

        $processHelper = $this->prophesize(ProcessHelper::class);
        $this->unitGit = new GitHelper(
            $processHelper->reveal(),
            $this->gitConfigHelper->reveal(),
            $this->filesystemHelper->reveal()
        );

        $processHelper->runCommand(['git', 'ls-remote', $remote], true)->willReturn($remoteList);

        $this->setExpectedException('\RuntimeException', sprintf('Invalid refs found while searching for remote branch at "refs/heads/%s"', $sourceBranch));
        $this->assertTrue($this->unitGit->remoteBranchExists($remote, $sourceBranch));
    }
}
