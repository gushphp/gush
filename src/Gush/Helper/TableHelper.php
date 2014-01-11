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

class TableHelper extends BaseTableHelper
{
    protected $footer;

    public function setLayout($layout)
    {
        if (is_string($layout)) {
            $layout = constant('Symfony\Component\Console\Helper\TableHelper::LAYOUT_' . strtoupper($layout));

            return parent::setLayout($layout);
        }

        return parent::setLayout($layout);
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
        parent::render($output);

        if ($this->footer) {
            $output->writeln('');
            $output->writeln($this->footer);
        }
    }
}
