<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Feature;

/**
 * The GitRepoSubscriber will act on classes implementing
 * this interface.
 *
 * This informs the GitRepoSubscriber to add extra options for
 * specifying the issue-tracker adapter-name, organization and repository.
 */
interface IssueTrackerRepoFeature extends GitRepoFeature
{
}
