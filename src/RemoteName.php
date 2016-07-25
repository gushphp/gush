<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

use Ddd\Slug\Infra\SlugGenerator\DefaultSlugGenerator;
use Ddd\Slug\Infra\Transliterator\LatinTransliterator;
use Ddd\Slug\Infra\Transliterator\TransliteratorCollection;

final class RemoteName
{
    private $org;
    private $name;
    private $host;
    private $fullName;

    public function __construct($org, $name, $host = null)
    {
        if ($host) {
            $host = trim(preg_replace('{^(https?|git(\+ssh)?)://}i', '', $host), '/');
        }

        $this->org = $org;
        $this->name = $name;
        $this->host = $host;

        if (null !== $host) {
            $hostString = (
                new DefaultSlugGenerator(new TransliteratorCollection([new LatinTransliterator()]), [])
            )->slugify((array) $host);

            $this->fullName = sprintf('%s_%s_%s', $org, $name, $hostString);
        } else {
            $this->fullName = sprintf('%s_%s', $org, $name);
        }
    }

    public function getOrg()
    {
        return $this->org;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function __toString()
    {
        return $this->fullName;
    }
}
