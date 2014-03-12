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
class SymfonyDocTemplate extends AbstractSymfonyTemplate
{
    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return [
            'doc-fix' => ['Doc Fix?', 'n'],
            'new-docs' => ['New Docs?', 'n'],
            'applies-to' => ['Applies to', ''],
            'fixed_tickets' => ['Fixed tickets', ''],
            'description' => ['Description', ''],
        ];
    }

    public function getName()
    {
        return 'pull-request-create/symfony-doc';
    }
}
