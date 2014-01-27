<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Feature;

/**
 * The TableSubscriber will act on classes implementing
 * this interface
 */
interface TemplateFeature
{
    /**
     * Return the domain for the template, e.g. pull-request.
     * This domain should correspond to the first part of the
     * template name, e.g. "pull-request/symfony-doc"
     *
     * @return string
     */
    public function getTemplateDomain();


    /**
     * Return the default template name
     *
     * @return string
     */
    public function getTemplateDefault();
}
