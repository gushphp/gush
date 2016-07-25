<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests;

use Gush\RemoteName;

final class RemoteNameTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_provides_access_to_values_with_empty_host()
    {
        $remoteName = new RemoteName('gushphp', 'gush');

        $this->assertEquals('gushphp', $remoteName->getOrg());
        $this->assertEquals('gush', $remoteName->getName());
        $this->assertNull($remoteName->getHost());
        $this->assertEquals('gushphp_gush', (string) $remoteName);
    }

    /**
     * @test
     */
    public function it_provides_access_to_values_with_host()
    {
        $remoteName = new RemoteName('gushphp', 'gush', 'https://github.com');

        $this->assertEquals('gushphp', $remoteName->getOrg());
        $this->assertEquals('gush', $remoteName->getName());
        $this->assertEquals('github.com', $remoteName->getHost());
        $this->assertEquals('gushphp_gush_github-com', (string) $remoteName);
    }
}
