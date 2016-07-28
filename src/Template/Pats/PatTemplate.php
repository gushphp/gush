<?php

/*
 * This file is part of Gush package.
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
            'pat' => 'pat_name',
        ];
    }

    public function getName()
    {
        return 'pats/general';
    }

    public function render()
    {
        if (empty($this->parameters)) {
            throw new \RuntimeException('Template has not been bound');
        }
        $pat = $this->parameters['pat'];
        $placeholders = $this->parameters;
        unset($placeholders['pat']);

        return $this->renderPat($placeholders, $pat);
    }

    private function renderPat(array $placeHolders, $pat)
    {
        $resultString = Pats::get($pat);
        foreach ($placeHolders as $placeholder => $value) {
            $resultString = str_replace('{{ '.$placeholder.' }}', $value, $resultString);
        }

        return $resultString;
    }
}
