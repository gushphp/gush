<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\PullRequest\Create;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class SymfonyTemplate extends AbstractSymfonyTemplate
{
    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return [
            'bug_fix' => ['Bug Fix?:', 'n'],
            'new_feature' => ['New Feature?:', 'n'],
            'bc_breaks' => ['BC Breaks?:', 'n'],
            'deprecations' => ['Deprecations?:', 'n'],
            'tests_pass' => ['Tests Pass?:', 'n'],
            'fixed_tickets' => ['Fixed Tickets:', ''],
            'license' => ['License:', 'MIT'],
            'doc_pr' => ['Doc PR:', ''],
            'description' => ['Description:', ''],
        ];
    }

    public function getName()
    {
        return 'pull-request-create/symfony';
    }
}
