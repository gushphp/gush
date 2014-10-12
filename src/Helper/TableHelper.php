<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TableHelper extends Helper implements InputAwareInterface
{
    const LAYOUT_GITHUB = 3;
    const LAYOUT_DEFAULT = 0;
    const LAYOUT_BORDERLESS = 1;
    const LAYOUT_COMPACT = 2;

    /**
     * @var string
     */
    protected $footer;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var Table
     */
    private $table;

    /**
     * @var array
     */
    protected $validLayouts = [
        'default',
        'borderless',
        'compact',
        'github',
    ];

    public function __construct()
    {
        $this->table = new Table(new NullOutput());
        $this->setLayout('default');
    }

    /**
     * {@inheritdoc}
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Sets table layout type.
     *
     * @param int $layout self::LAYOUT_*
     *
     * @throws \InvalidArgumentException
     * @return TableHelper
     */
    public function setLayout($layout)
    {
        if (is_string($layout)) {
            $layout = constant('Gush\Helper\TableHelper::LAYOUT_'.strtoupper($layout));
        }

        switch ($layout) {
            case self::LAYOUT_BORDERLESS:
                $this->table->setStyle('borderless');
                break;

            case self::LAYOUT_COMPACT:
                $this->table->setStyle('compact');
                break;

            case self::LAYOUT_DEFAULT:
                $this->table->setStyle('default');
                break;

            case self::LAYOUT_GITHUB:
                $this->table->getStyle()
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
                throw new \InvalidArgumentException(sprintf('Invalid table layout "%s".', $layout));
        };

        return $this;
    }

    /**
     * @param array $rows
     * @param       $rowFormatter
     */
    public function formatRows(array $rows, $rowFormatter)
    {
        foreach ($rows as $row) {
            $formattedRow = call_user_func($rowFormatter, $row);
            $this->table->addRow($formattedRow);
        }
    }

    /**
     * @param $footer
     */
    public function setFooter($footer)
    {
        $this->footer = $footer;
    }

    /**
     * {@inheritdoc}
     */
    public function render(OutputInterface $output)
    {
        $p = new \ReflectionProperty($this->table, 'output');
        $p->setAccessible(true);
        $p->setValue($this->table, $output);

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

        $this->table->render();

        if ($this->footer) {
            $output->writeln('');
            $output->writeln($this->footer);
        }
    }

    public function setHeaders(array $headers)
    {
        $this->table->setHeaders($headers);

        return $this;
    }

    public function setRows(array $rows)
    {
        $this->table->setRows($rows);

        return $this;
    }

    public function addRows(array $rows)
    {
        $this->table->addRows($rows);

        return $this;
    }

    public function addRow(array $row)
    {
        $this->table->addRow($row);

        return $this;
    }

    public function setRow($column, array $row)
    {
        $this->table->setRow($column, $row);

        return $this;
    }

    /**
     * Sets padding character, used for cell padding.
     *
     * @param string $paddingChar
     *
     * @return TableHelper
     */
    public function setPaddingChar($paddingChar)
    {
        $this->table->getStyle()->setPaddingChar($paddingChar);

        return $this;
    }

    /**
     * Sets horizontal border character.
     *
     * @param string $horizontalBorderChar
     *
     * @return TableHelper
     */
    public function setHorizontalBorderChar($horizontalBorderChar)
    {
        $this->table->getStyle()->setHorizontalBorderChar($horizontalBorderChar);

        return $this;
    }

    /**
     * Sets vertical border character.
     *
     * @param string $verticalBorderChar
     *
     * @return TableHelper
     */
    public function setVerticalBorderChar($verticalBorderChar)
    {
        $this->table->getStyle()->setVerticalBorderChar($verticalBorderChar);

        return $this;
    }

    /**
     * Sets crossing character.
     *
     * @param string $crossingChar
     *
     * @return TableHelper
     */
    public function setCrossingChar($crossingChar)
    {
        $this->table->getStyle()->setCrossingChar($crossingChar);

        return $this;
    }

    /**
     * Sets header cell format.
     *
     * @param string $cellHeaderFormat
     *
     * @return TableHelper
     */
    public function setCellHeaderFormat($cellHeaderFormat)
    {
        $this->table->getStyle()->setCellHeaderFormat($cellHeaderFormat);

        return $this;
    }

    /**
     * Sets row cell format.
     *
     * @param string $cellRowFormat
     *
     * @return TableHelper
     */
    public function setCellRowFormat($cellRowFormat)
    {
        $this->table->getStyle()->setCellHeaderFormat($cellRowFormat);

        return $this;
    }

    /**
     * Sets row cell content format.
     *
     * @param string $cellRowContentFormat
     *
     * @return TableHelper
     */
    public function setCellRowContentFormat($cellRowContentFormat)
    {
        $this->table->getStyle()->setCellRowContentFormat($cellRowContentFormat);

        return $this;
    }

    /**
     * Sets table border format.
     *
     * @param string $borderFormat
     *
     * @return TableHelper
     */
    public function setBorderFormat($borderFormat)
    {
        $this->table->getStyle()->setBorderFormat($borderFormat);

        return $this;
    }

    /**
     * Sets cell padding type.
     *
     * @param int $padType STR_PAD_*
     *
     * @return TableHelper
     */
    public function setPadType($padType)
    {
        $this->table->getStyle()->setPadType($padType);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'table';
    }
}
