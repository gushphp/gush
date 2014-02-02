<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\TableHelper as BaseTableHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class TableHelper extends BaseTableHelper implements InputAwareInterface
{
    const LAYOUT_GITHUB = 3;

    protected $footer;
    protected $input;

    protected $validLayouts = [
        'default',
        'borderless',
        'compact',
        'github',
    ];

    public function __construct()
    {
        $this->setLayout('default');
    }

    /**
     * {@inheritDoc}
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     */
    public function setLayout($layout)
    {
        if (is_string($layout)) {
            $layout = constant('Gush\Helper\TableHelper::LAYOUT_'.strtoupper($layout));
        }

        switch ($layout) {
            case self::LAYOUT_BORDERLESS:
                $this
                    ->setPaddingChar(' ')
                    ->setHorizontalBorderChar('=')
                    ->setVerticalBorderChar(' ')
                    ->setCrossingChar(' ')
                    ->setCellHeaderFormat('<info>%s</info>')
                    ->setCellRowFormat('%s')
                    ->setCellRowContentFormat(' %s ')
                    ->setBorderFormat('%s')
                    ->setPadType(STR_PAD_RIGHT)
                ;
                break;

            case self::LAYOUT_COMPACT:
                $this
                    ->setPaddingChar(' ')
                    ->setHorizontalBorderChar('')
                    ->setVerticalBorderChar(' ')
                    ->setCrossingChar('')
                    ->setCellHeaderFormat('<info>%s</info>')
                    ->setCellRowFormat('%s')
                    ->setCellRowContentFormat('%s')
                    ->setBorderFormat('%s')
                    ->setPadType(STR_PAD_RIGHT)
                ;
                break;

            case self::LAYOUT_DEFAULT:
                $this
                    ->setPaddingChar(' ')
                    ->setHorizontalBorderChar('-')
                    ->setVerticalBorderChar('|')
                    ->setCrossingChar('+')
                    ->setCellHeaderFormat('<info>%s</info>')
                    ->setCellRowFormat('%s')
                    ->setCellRowContentFormat(' %s ')
                    ->setBorderFormat('%s')
                    ->setPadType(STR_PAD_RIGHT)
                ;
                break;

            case self::LAYOUT_GITHUB:
                $this
                    ->setPaddingChar(' ')
                    ->setHorizontalBorderChar(' ')
                    ->setVerticalBorderChar('|')
                    ->setCrossingChar(' ')
                    ->setCellHeaderFormat('<info>%s</info>')
                    ->setCellRowFormat('%s')
                    ->setCellRowContentFormat('%s')
                    ->setBorderFormat('%s')
                    ->setPadType(STR_PAD_RIGHT)
                ;
                break;

            default:
                throw new InvalidArgumentException(sprintf('Invalid table layout "%s".', $layout));
                break;
        };

        return $this;
    }

    public function formatRows(array $rows, $rowFormatter)
    {
        foreach ($rows as $row) {
            $formattedRow = call_user_func($rowFormatter, $row);
            $this->addRow($formattedRow);
        }
    }

    public function setFooter($footer)
    {
        $this->footer = $footer;
    }

    public function render(OutputInterface $output)
    {
        if (null !== $this->input) {
            $layout = $this->input->getOption('table-layout');

            if (!in_array($layout, $this->validLayouts)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Table layout "%s" is not valid, must be one of: %s',
                        $layout,
                        implode(', ', $this->validLayouts)
                    )
                );
            }

            $this->setLayout($layout);

            if (true === $this->input->getOption('table-no-header')) {
                $this->setHeaders([]);
            }

            if (true === $this->input->getOption('table-no-footer')) {
                $this->footer = null;
            }
        }

        parent::render($output);

        if ($this->footer) {
            $output->writeln('');
            $output->writeln($this->footer);
        }
    }
}
