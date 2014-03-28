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

class GitHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Gush\Helper\GitHelper
     */
    protected $git;

    public function setUp()
    {
        $this->git = new GitHelper();
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
     */
    public function it_gets_the_repository_name()
    {
        $this->assertEquals('gush', $this->git->getRepoName());
    }

    /**
     * @test
     */
    public function it_gets_the_vendor_name_of_the_repository()
    {
        $this->assertEquals(getenv('GIT_VENDOR_NAME'), $this->git->getVendorName());
    }

    /**
     * @test
     */
    public function it_runs_git_command()
    {
        $this->markTestIncomplete('needs to be written');
    }
}
