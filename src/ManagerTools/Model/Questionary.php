<?php

namespace ManagerTools\Model;

interface Questionary
{
    /**
     * @return Question[]
     */
    public function getQuestions();
    public function getHeaders();
}