<?php

/*
 * This file is part of the Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Model;

class Question
{
    protected $statement;
    protected $validator;
    protected $attempt;
    protected $default;
    protected $autocomplete;

    public function __construct($statement)
    {
        $this->statement = $statement;
        $this->validator = function ($answer) {
            return $answer;
        };
        $this->default = null;
        $this->attempt = false;
        $this->autocomplete = null;
    }

    /**
     * @return mixed
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * @param mixed $statement
     */
    public function setStatement($statement)
    {
        $this->statement = $statement;
    }

    /**
     * @return mixed
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * @param mixed $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @return mixed
     */
    public function getAttempt()
    {
        return $this->attempt;
    }

    /**
     * @param mixed $attempt
     */
    public function setAttempt($attempt)
    {
        $this->attempt = $attempt;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * @return mixed
     */
    public function getAutocomplete()
    {
        return $this->autocomplete;
    }

    /**
     * @param mixed $autocomplete
     */
    public function setAutocomplete($autocomplete)
    {
        $this->autocomplete = $autocomplete;
    }
}
