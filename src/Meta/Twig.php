<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Meta;

class Twig implements MetaInterface
{
    /**
     * {@inheritDoc}
     */
    public function getStartDelimiter()
    {
        return '{##';
    }

    /**
     * {@inheritDoc}
     */
    public function getDelimiter()
    {
        return '#';
    }

    /**
     * {@inheritDoc}
     */
    public function getEndDelimiter()
    {
        return '#}';
    }
} 