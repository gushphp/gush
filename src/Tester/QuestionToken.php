<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tester;

use Prophecy\Argument\Token\TokenInterface;
use Prophecy\Util\StringUtil;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class QuestionToken implements TokenInterface
{
    /**
     * @var ChoiceQuestion|Question
     */
    private $value;
    private $string;
    private $util;

    /**
     * Initializes token.
     *
     * @param ChoiceQuestion|Question $value
     * @param StringUtil              $util
     */
    public function __construct($value, StringUtil $util = null)
    {
        $this->value = $value;
        $this->util  = $util ?: new StringUtil();
    }

    /**
     * Scores 10 if argument matches preset value.
     *
     * @param ChoiceQuestion|Question $argument
     *
     * @return bool|int
     */
    public function scoreArgument($argument)
    {
        if (!is_object($argument) || get_class($argument) !== get_class($this->value)) {
            return false;
        }

        if (false === strpos($argument->getQuestion(), $this->value->getQuestion())) {
            return false;
        }

        if ($this->value->getDefault() !== $argument->getDefault()) {
            return false;
        }

        if ($this->value instanceof ChoiceQuestion && $this->value->getChoices() !== $argument->getChoices()) {
            return false;
        }

        return 10;
    }

    /**
     * Returns preset value against which token checks arguments.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns false.
     *
     * @return bool
     */
    public function isLast()
    {
        return false;
    }

    /**
     * Returns string representation for token.
     *
     * @return string
     */
    public function __toString()
    {
        if (null === $this->string) {
            $this->string = sprintf('question(%s)', $this->util->stringify($this->value));
        }

        return $this->string;
    }
}
