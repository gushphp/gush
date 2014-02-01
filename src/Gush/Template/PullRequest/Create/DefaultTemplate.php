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
 * DefaultTemplate
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class DefaultTemplate extends AbstractTemplate
{
    public function render()
    {
        $out = [];

        return implode("\n", $out);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return [];
    }

    public function getName()
    {
        return 'pull-request-create/default';
    }
}
