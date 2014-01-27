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

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * SymfonyTemplate
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class SymfonyTemplate extends AbstractSymfonyTemplate
{
    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return array(
            'bug_fix' => array('Bug Fix?:', 'n'),
            'new_feature' => array('New Feature?:', 'n'),
            'bc_breaks' => array('BC Breaks?:', 'n'),
            'deprecations' => array('Deprecations?:', 'n'),
            'tests_pass' => array('Tests Pass?:', 'n'),
            'fixed_tickets' => array('Fixed Tickets:', ''),
            'license' => array('License:', 'MIT'),
            'doc_pr' => array('Doc PR:', ''),
            'description' => array('Description:', ''),
        );
    }

    public function getName()
    {
        return 'pull-request-create/symfony';
    }
}

