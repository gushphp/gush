<?php

/*
 * This file is part of the Manager Tools.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ManagerTools\Model;

class SymfonyQuestionary implements Questionary
{
    /**
     * {@inheritdoc}
     */
    public function getQuestions()
    {
        $questionArray = array(
            array('Bug fix?', 'y'),
            array('New feature?', 'n'),
            array('BC breaks?', 'n'),
            array('Deprecations?', 'n'),
            array('Tests pass?', 'y'),
            array('Fixed tickets', '#000'),
            array('License', 'MIT'),
            array('Doc PR', ''),
        );

        $questions = array();

        foreach ($questionArray as $question) {
            $q = new Question($question[0]);
            $q->setDefault($question[1]);
            $questions[] = $q;
        }

        return $questions;
    }

    public function getHeaders()
    {
        return array('Q', 'A');
    }
}