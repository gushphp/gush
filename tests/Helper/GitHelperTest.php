<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\GitHelper;
use Gush\Helper\ProcessHelper;

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
     * @var ProcessHelper
     */
    protected $processHelper;

    public function setUp()
    {
        $this->processHelper = $this->getMock('Gush\Helper\ProcessHelper');
        $this->git = new GitHelper(new ProcessHelper());
        $this->unitGit = new GitHelper($this->processHelper);
    }

    /**
     * @test
     */
    public function it_bears_the_right_helper_name()
    {
        $this->assertEquals('git', $this->git->getName());
    }

    /**
     * @test
     */
    public function it_gets_current_git_branch_name()
    {
        exec('git rev-parse --abbrev-ref HEAD', $output);
        $this->assertEquals($output[0], $this->git->getBranchName());
    }

    /**
     * @test
     */
    public function it_gets_the_last_tag_on_the_current_branch()
    {
        exec('git describe --tags --abbrev=0 HEAD', $output);
        $this->assertEquals($output[0], $this->git->getLastTagOnCurrentBranch());
    }

    /**
     * @test
     * @dataProvider repoUrlProvider
     */
    public function it_gets_the_repository_name($repo)
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
    public function it_gets_the_vendor_name_of_the_repository($repo)
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
    public function it_runs_git_command()
    {
        $return = '## master';
        $this->processHelper
            ->expects($this->any())
            ->method('runCommand')
            ->will($this->returnValue($return))
        ;

        $this->assertContains('## master', $this->unitGit->runGitCommand('git status --branch --short'));
    }

    /**
     * @test
     */
    public function it_lists_files()
    {
        // Smoke test for a real listFiles
        $res = $this->git->listFiles();
        $this->assertGreaterThan(50, $res);
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
