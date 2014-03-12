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
    public function itBearsTheRightHelperName()
    {
        $this->assertEquals('git', $this->git->getName());
    }

    /**
     * @test
     */
    public function itGetsCurrentGitBranchName()
    {
        exec('git rev-parse --abbrev-ref HEAD', $output);
        $this->assertEquals($output[0], $this->git->getBranchName());
    }

    /**
     * @test
     */
    public function itGetsTheLastTagOnTheCurrentBranch()
    {
        exec('git describe --tags --abbrev=0 HEAD', $output);
        $this->assertEquals($output[0], $this->git->getLastTagOnCurrentBranch());
    }

    /**
     * @test
     */
    public function itGetsTheRepositoryName()
    {
        $this->assertEquals('gush', $this->git->getRepoName());
    }

    /**
     * @test
     */
    public function itGetsTheVendorNameOfTheRepository()
    {
        $this->assertEquals(getenv('GIT_VENDOR_NAME'), $this->git->getVendorName());
    }

    /**
     * @test
     */
    public function itRunsGitCommand()
    {
        $this->markTestIncomplete('needs to be written');
    }
}
