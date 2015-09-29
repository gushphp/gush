<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GushQuestionHelper extends SymfonyQuestionHelper
{
    /**
     * @var int
     */
    private $attempts;

    /**
     * Set the maximum attempts when none is set.
     *
     * This should only be used for testing.
     *
     * @param int $attempts
     */
    public function setMaxAttempts($attempts)
    {
        $this->attempts = $attempts;
    }

    /**
     * {@inheritdoc}
     */
    public function ask(InputInterface $input, OutputInterface $output, Question $question)
    {
        if (null !== $this->attempts && null === $question->getMaxAttempts()) {
            $question->setMaxAttempts($this->attempts);
        }

        return QuestionHelper::ask($input, $output, $question);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gush_question';
    }
}
