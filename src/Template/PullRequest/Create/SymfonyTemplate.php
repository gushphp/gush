<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\PullRequest\Create;

class SymfonyTemplate extends AbstractSymfonyTemplate
{
    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return [
            'branch' => ['Branch', 'master'],
            'bug_fix' => ['Bug fix?', 'n'],
            'new_feature' => ['New feature?', 'n'],
            'bc_breaks' => ['BC breaks?', 'n'],
            'deprecations' => ['Deprecations?', 'n'],
            'tests_pass' => ['Tests pass?', 'n'],
            'fixed_tickets' => ['Fixed tickets', ''],
            'license' => ['License', 'MIT'],
            'doc_pr' => ['Doc PR', ''],
            'description' => ['Description', ''],
        ];
    }

    public function getName()
    {
        return 'pull-request-create/symfony';
    }
}
