<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

class BaseDocumentation implements DocumentationInterface
{
    /**
     * An array with the available issue tokens and their description
     *
     * @var array
     */
    public static $issueTokens = [
        'number' => 'The Issue ID',
        'title'  => 'Issue title'
    ];

    /**
     * {@inheritdoc}
     */
    public function getIssueTokens()
    {
        return static::$issueTokens;
    }
}
