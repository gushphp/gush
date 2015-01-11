<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Meta;

class Base implements Meta
{
    /**
     * {@inheritdoc}
     */
    public function getStartDelimiter()
    {
        return '/*';
    }

    /**
     * {@inheritdoc}
     */
    public function getDelimiter()
    {
        return '*';
    }

    /**
     * {@inheritdoc}
     */
    public function getEndDelimiter()
    {
        return '*/';
    }

    /**
     * {@inheritdoc}
     */
    public function getStartTokenRegex()
    {
        return '{^(<\?(php)?\s+)|<%|(<\?xml[^>]+)}i';
    }
}
