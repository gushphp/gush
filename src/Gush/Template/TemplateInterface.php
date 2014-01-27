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
 * TemplateInterface
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
interface TemplateInterface
{
    /**
     * Render the template using the given parameters
     */
    public function render();

    public function bind($params);

    /**
     * Return all the variables required by the template
     * including descriptions and default values.
     *
     * The user will be prompted for any missing variables.
     */
    public function getRequirements();

    /**
     * Return the name of this template
     */
    public function getName();
}
