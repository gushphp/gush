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

use Gush\Template\AbstractTemplate;

class DefaultTemplate extends AbstractTemplate
{
    public function render()
    {
        return $this->parameters['description'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return [
            'description' => ['Description', ''],
        ];
    }

    public function getName()
    {
        return 'pull-request-create/default';
    }
}
