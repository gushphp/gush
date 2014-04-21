<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\PullRequest\Create;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class EnterpriseTemplate extends AbstractSymfonyTemplate
{
    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return [
            'bug_fix' => ['Bug Fix?', 'no'],
            'new_feature' => ['New Feature?', 'no'],
            'bc_breaks' => ['BC Breaks?', 'no'],
            'deprecations' => ['Deprecations?', 'no'],
            'tests_pass' => ['Tests Pass?', 'yes'],
            'fixed_tickets' => ['Fixed Tickets', ''],
            'license' => ['License', 'Proprietary'],
            'doc_pr' => ['Doc PR', ''],
            'description' => ['Description', ''],
        ];
    }

    public function getName()
    {
        return 'pull-request-create/enterprise';
    }
}
