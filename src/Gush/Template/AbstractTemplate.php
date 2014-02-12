<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class AbstractTemplate implements TemplateInterface
{
    protected $parameters = null;

    public function bind($parameters)
    {
        $this->parameters = $parameters;

        $requirements = $this->getRequirements();

        foreach ($requirements as $key => $requirementData) {
            list($label, $default) = $requirementData;

            if (!isset($this->parameters[$key])) {
                $this->parameters[$key] = $default;
            }
        }
    }
}
