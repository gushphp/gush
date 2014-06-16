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
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;

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
                break;
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {

    }
}
