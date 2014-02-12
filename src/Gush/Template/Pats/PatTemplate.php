<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\Pats;

use Gush\Template\AbstractTemplate;

class PatTemplate extends AbstractTemplate
{
    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return [
            'author' => 'author_place_holder',
        ];
    }

    public function getName()
    {
        return 'pats/general';
    }

    public function render()
    {
        if (null === $this->parameters) {
            throw new \RuntimeException('Template has not been bound');
        }

        return $this->renderRandomPat($this->parameters);
    }

    private function renderRandomPat(array $placeHolders)
    {
        $resultString = Pats::getRandom();
        foreach ($placeHolders as $placeholder => $value) {
            $resultString = str_replace('{{ '.$placeholder.' }}', $value, $resultString);
        }

        return $resultString;
    }
}
