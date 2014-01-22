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

class SymfonyDocumentationQuestionary implements Questionary
{
    public function getQuestions()
    {
        $questionArray = [
            ['Doc fix?', 'yes'],
            ['New docs?', 'no'],
            ['Applies to', '2.3+'],
            ['Fixed tickets', '#000'],
            ['License', 'CC-ASA 3.0 Unported'],
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
