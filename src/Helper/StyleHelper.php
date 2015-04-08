<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StyleHelper extends Helper implements OutputAwareInterface, InputAwareInterface, StyleInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var StyleInterface
     */
    private $style;

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
        $this->style = null;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        $this->style = null;
    }

    public function getName()
    {
        return 'gush_style';
    }

    /**
     * @return SymfonyStyle
     *
     * @internal
     */
    public function getStyle()
    {
        if ($this->style === null) {
            $this->style = new SymfonyStyle($this->input, $this->output);
        }

        return $this->style;
    }

    /**
     * {@inheritdoc}
     */
    public function newLine($count = 1)
    {
        $this->getStyle()->newLine($count);
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $type = SymfonyStyle::OUTPUT_NORMAL)
    {
        $this->getStyle()->write($messages, $newline, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, $type = SymfonyStyle::OUTPUT_NORMAL)
    {
        $this->getStyle()->writeln($messages, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity($level)
    {
        $this->output->setVerbosity($level);
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity()
    {
        return $this->output->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated)
    {
        $this->output->setDecorated($decorated);
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated()
    {
        return $this->output->isDecorated();
    }

    /**
     * Formats a message as a block of text.
     *
     * @param string|array $messages  The message to write in the block
     * @param string|null  $type      The block type (added in [] on first line)
     * @param string|null  $style     The style to apply to the whole block
     * @param string       $prefix    The prefix for the block
     * @param bool         $padding   Whether to add vertical padding
     */
    public function block($messages, $type = null, $style = null, $prefix = ' ', $padding = false)
    {
        $this->getStyle()->block($messages, $type, $style, $prefix, $padding);
    }

    /**
     * {@inheritdoc}
     */
    public function title($message)
    {
        $this->getStyle()->title($message);
    }

    /**
     * {@inheritdoc}
     */
    public function section($message)
    {
        $this->getStyle()->section($message);
    }

    /**
     * {@inheritdoc}
     */
    public function listing(array $elements)
    {
        $this->getStyle()->listing($elements);
    }

    /**
     * {@inheritdoc}
     */
    public function text($message)
    {
        $this->getStyle()->text($message);
    }

    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->getStyle()->success($message);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->getStyle()->error($message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->getStyle()->warning($message);
    }

    /**
     * {@inheritdoc}
     */
    public function note($message)
    {
        $this->getStyle()->note($message);
    }

    /**
     * {@inheritdoc}
     */
    public function caution($message)
    {
        $this->getStyle()->caution($message);
    }

    /**
     * {@inheritdoc}
     */
    public function table(array $headers, array $rows)
    {
        $this->getStyle()->table($headers, $rows);
    }

    /**
     * {@inheritdoc}
     */
    public function ask($question, $default = null, $validator = null)
    {
        return $this->getStyle()->ask($question, $default, $validator);
    }

    /**
     * {@inheritdoc}
     */
    public function askHidden($question, $validator = null)
    {
        return $this->getStyle()->askHidden($question, $validator);
    }

    /**
     * {@inheritdoc}
     */
    public function confirm($question, $default = true)
    {
        return $this->getStyle()->confirm($question, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function choice($question, array $choices, $default = null)
    {
        return $this->getStyle()->choice($question, $choices, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function progressStart($max = 0)
    {
        $this->getStyle()->progressStart($max);
    }

    /**
     * {@inheritdoc}
     */
    public function progressAdvance($step = 1)
    {
        $this->getStyle()->progressAdvance($step);
    }

    /**
     * {@inheritdoc}
     */
    public function progressFinish()
    {
        $this->getStyle()->progressFinish();
    }

    /**
     * {@inheritdoc}
     */
    public function createProgressBar($max = 0)
    {
        return $this->getStyle()->createProgressBar($max);
    }

    /**
     * @param Question $question
     *
     * @return string
     */
    public function askQuestion(Question $question)
    {
        return $this->getStyle()->askQuestion($question);
    }
}
