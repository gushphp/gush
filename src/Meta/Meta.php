<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Meta;

interface Meta
{
    /**
     * @return string
     */
    public function getStartDelimiter();

    /**
     * @return string
     */
    public function getDelimiter();

    /**
     * @return string
     */
    public function getEndDelimiter();

    /**
     * @return string|null
     */
    public function getStartTokenRegex();
}
