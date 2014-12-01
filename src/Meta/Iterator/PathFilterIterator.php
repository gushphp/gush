<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Meta\Iterator;

use Symfony\Component\Finder\Expression\Expression;
use Symfony\Component\Finder\Iterator\PathFilterIterator as BasePathFilterIterator;

/**
 * PathFilterIterator filters files using patterns (regexps, globs or strings).
 *
 * Overwritten as we don't use full locations.
 */
class PathFilterIterator extends BasePathFilterIterator
{
    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        $filename = $this->current();

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $filename = strtr($filename, '\\', '/');
        }

        // should at least not match one rule to exclude
        foreach ($this->noMatchRegexps as $regex) {
            if (preg_match($regex, $filename)) {
                return false;
            }
        }

        // should at least match one rule
        $match = true;
        if ($this->matchRegexps) {
            $match = false;
            foreach ($this->matchRegexps as $regex) {
                if (preg_match($regex, $filename)) {
                    return true;
                }
            }
        }

        return $match;
    }

    /**
     * Converts glob to regexp.
     *
     * PCRE patterns are left unchanged.
     * Glob strings are transformed with Glob::toRegex().
     *
     * @param string $str Pattern: glob or regexp
     *
     * @return string regexp corresponding to a given glob or regexp
     */
    protected function toRegex($str)
    {
        $expression = Expression::create($str);

        if ($expression->isGlob()) {
            $regex = $expression->getRegex();

            // We don't the starts-with operator '^'
            return $regex::BOUNDARY.substr($regex->render(), strlen($regex::BOUNDARY)+1);
        }

        return $expression->getRegex()->render();
    }
}
