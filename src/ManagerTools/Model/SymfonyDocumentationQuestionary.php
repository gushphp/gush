<?php

namespace ManagerTools\Model;

class SymfonyDocumentationQuestionary implements Questionary
{
    public function getQuestions()
    {
        $questionArray = array(
            array('Bug fix?', 'yes|no'),
            array('New feature?', 'yes|no'),
            array('BC breaks?', 'yes|no'),
            array('Deprecations?', 'yes|no'),
            array('Tests pass?', 'yes|no'),
            array('Fixed tickets', 'comma separated list of tickets fixed by the PR'),
            array('License', 'MIT'),
            array('Doc PR', 'The reference to the documentation PR if any'),
        );

        $questions = array();

        foreach ($questionArray as $statement) {
            $questions[] = new Question($statement);
        }

        return $questions;
    }

    public function getHeaders()
    {
        return array('Q', 'A');
    }
}