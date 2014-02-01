<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\PullRequest\Create;

use Gush\Template\AbstractTemplate;
use Gush\Helper\TableHelper;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class AbstractSymfonyTemplate extends AbstractTemplate
{
    public function render()
    {
        if (null === $this->parameters) {
            throw new \RuntimeException('Template has not been bound');
        }

        $output = new BufferedOutput();
        $table = new TableHelper();
        $table->setHeaders(array('Q', 'A'));
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

        return implode("\n", $out);
    }
}
