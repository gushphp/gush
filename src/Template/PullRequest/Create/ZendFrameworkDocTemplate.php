<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\PullRequest\Create;

class ZendFrameworkDocTemplate extends AbstractSymfonyTemplate
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
        return 'pull-request-create/zendframework-doc';
    }
}
