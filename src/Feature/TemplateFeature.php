<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Feature;

/**
 * The TemplateSubscriber will act on classes implementing
 * this interface
 */
interface TemplateFeature
{
    /**
     * Returns the domain for the template, e.g. pull-request-create.
     * This domain should correspond to the first part of the
     * template name, e.g. "pull-request-create/symfony-doc"
     *
     * @return string
     */
    public function getTemplateDomain();

    /**
     * Returns the default template name
     *
     * @return string
     */
    public function getTemplateDefault();
}
