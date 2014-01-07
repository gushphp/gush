<?php

namespace Gush\Table;

use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Output\OutputInterface;

class Tabulator
{
    public function createTable()
    {
        return new TableHelper();
    }

    public function tabulate(TableHelper $table, $items, $rowBuilderCallback)
    {
        foreach ($items as $item) {
            $transformedRow = call_user_func($rowBuilderCallback, $item);
            $table->addRow($transformedRow);
        }
    }

    public function applyLayout(TableHelper $table, $layoutLabel = TableHelper::LAYOUT_BORDERLESS)
    {
        $table
            ->setLayout($layoutLabel)
            ->setHorizontalBorderChar('')
            ->setPaddingChar(' ')
            ->setVerticalBorderChar('')
        ;
    }

    public function render(OutputInterface $output, TableHelper $table, $layoutLabel = TableHelper::LAYOUT_BORDERLESS)
    {
        $this->applyLayout($table, $layoutLabel);

        $table->render($output);
    }
}
