<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\ThirdParty\Jira;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class JiraEnterpriseIssueTracker extends JiraIssueTracker
{
    /**
     * {@inheritdoc}
     */
    public function supportsRepository($remoteUrl)
    {
        // always returns false as its not safe to determine this (yet)
        return false;
    }
}
