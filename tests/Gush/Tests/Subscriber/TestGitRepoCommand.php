<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Subscriber;

use Gush\Feature\GitHubFeature;
use Symfony\Component\Console\Command\Command;

class TestGitRepoCommand extends Command implements GitHubFeature
{
}
