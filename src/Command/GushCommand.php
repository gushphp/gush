<?php

namespace Gush\Command;

interface GushCommand
{
    /**
     * @return \Gush\Application
     */
    public function getGushApplication();
}