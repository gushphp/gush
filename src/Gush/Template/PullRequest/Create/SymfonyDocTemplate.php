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

use Gush\Template\AbstractTemplate;

/**
 * SymfonyDocTemplate
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class SymfonyDocTemplate extends AbstractSymfonyTemplate
{
    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return array(
            'doc-fix' => array('Doc Fix?:', 'n'),
            'new-docs' => array('New Docs?:', 'n'),
            'applies-to' => array('Applies to:', ''),
            'fixed_tickets' => array('Fixed tickets:', ''),
            'description' => array('Description:', ''),
        );
    }

    public function getName()
    {
        return 'pull-request-create/symfony-doc';
    }
}

