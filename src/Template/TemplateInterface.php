<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template;

interface TemplateInterface
{
    /**
     * Renders the template using the given parameters.
     *
     * @return string
     */
    public function render();

    /**
     * @param array
     */
    public function bind(array $params);

    /**
     * Returns all the variables required by the template
     * including descriptions and default values.
     *
     * The user will be prompted for any missing variables.
     *
     * @return array
     */
    public function getRequirements();

    /**
     * Returns the name of this template.
     *
     * @return string
     */
    public function getName();
}
