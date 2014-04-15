<?php

namespace Gush\Helper;

use Symfony\Component\Console\Output\OutputInterface;

interface OutputAwareInterface
{
    /**
     * @return void
     */
    public function setOutput(OutputInterface $output);
}