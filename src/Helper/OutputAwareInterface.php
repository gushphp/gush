<?php

namespace Gush\Helper;

use Symfony\Component\Console\Output\OutputInterface;

interface OutputAwareInterface
{
    /**
     * @param OutputInterface $output
     *
     * @return mixed
     */
    public function setOutput(OutputInterface $output);
}
