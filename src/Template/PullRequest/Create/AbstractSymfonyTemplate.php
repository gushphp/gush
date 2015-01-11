<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\PullRequest\Create;

use Gush\Helper\TableHelper;
use Gush\Template\AbstractTemplate;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class AbstractSymfonyTemplate extends AbstractTemplate
{
    public function render()
    {
        if (null === $this->parameters) {
            throw new \RuntimeException('Template has not been bound');
        }

        $questionaryHeaders = ['Q', 'A'];
        $output = new BufferedOutput();
        $table = new TableHelper();
        $table->addRow($questionaryHeaders);
        $table->addRow(array_fill(0, count($questionaryHeaders), '---'));
        $table->setLayout(TableHelper::LAYOUT_GITHUB);

        $description = $this->parameters['description'];
        unset($this->parameters['description']);
        $requirements = $this->getRequirements();

        foreach ($this->parameters as $key => $value) {
            $label = $requirements[$key][0];
            $table->addRow([$label, $value]);
        }

        $table->render($output);

        $out = [];
        $out[] = $output->fetch();
        $out[] = $description;

        return implode(PHP_EOL, $out);
    }
}
