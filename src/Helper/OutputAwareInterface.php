<?php

namespace Gush\Helper;

use Symfony\Component\Console\Output\OutputInterface;

interface OutputAwareInterface
{
    public function setOutput(OutputInterface $output);
}