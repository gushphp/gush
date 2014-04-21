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

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class Base implements Meta
{
    /**
     * {@inheritDoc}
     */
    public function getStartDelimiter()
    {
        return '/**';
    }

    /**
     * {@inheritDoc}
     */
    public function getDelimiter()
    {
        return '*';
    }

    /**
     * {@inheritDoc}
     */
    public function getEndDelimiter()
    {
        return '*/';
    }
}
