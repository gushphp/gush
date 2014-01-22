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

class SymfonyQuestionary implements Questionary
{
    /**
     * {@inheritdoc}
     */
    public function getQuestions()
    {
        $questionArray = [
            ['Bug fix?', 'y'],
            ['New feature?', 'n'],
            ['BC breaks?', 'n'],
            ['Deprecations?', 'n'],
            ['Tests pass?', 'y'],
            ['Fixed tickets', '#000'],
            ['License', 'MIT'],
            ['Doc PR', ''],
        ];

        $questions = [];

        foreach ($questionArray as $question) {
            $q = new Question($question[0]);
            $q->setDefault($question[1]);
            $questions[] = $q;
        }

        return $questions;
    }

    public function getHeaders()
    {
        return ['Q', 'A'];
    }
}
