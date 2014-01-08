<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Model;

interface Questionary
{
    /**
     * @return Question[]
     */
    public function getQuestions();
    public function getHeaders();
}
