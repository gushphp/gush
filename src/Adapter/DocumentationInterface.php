<?php

namespace Gush\Adapter;

interface DocumentationInterface
{
    /**
     * Returns an array with the available issue tokens and their description
     *
     * @return array
     */
    public function getIssueTokens();
}
