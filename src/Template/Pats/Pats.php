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

class Pats
{
    const GOOD_JOB = <<<EOT
Good job @{{ author }}.
EOT;

    const YOU_WERE_FAST = <<<EOT
You were fast on this one, thanks @{{ author }}.
EOT;

    const PATCH = <<<EOT
Good catch @{{ author }}, thanks for the patch.
EOT;

    const THANK_YOU = <<<EOT
Thank you @{{ author }}.
EOT;

    const GOOD_CATCH_THANKS = <<<EOT
Good catch, thanks @{{ author }}.
EOT;

    const THANKS_FOR_PR = <<<EOT
Thanks @{{ author }} for the pull request!
EOT;

    const WELL_DONE = <<<EOT
Well done @{{ author }}.
EOT;

    const BEERS = <<<EOT
:beers: @{{ author }}.
EOT;

    /**
     * @var string[]
     */
    protected static $pats = [];

    /**
     * @return string[]
     */
    public static function getPats()
    {
        if (!self::$pats) {
            $r = new \ReflectionClass(get_class());
            $pats = array_flip($r->getConstants());
            array_walk($pats, function(&$value) {
                $value = strtolower($value);
            });

            self::$pats = array_flip($pats);
        }

        return self::$pats;
    }

    /**
     * @param string[] $pats
     */
    public static function addPats(array $pats)
    {
        self::$pats = $pats + self::getPats();
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public static function get($name)
    {
        $pats = self::getPats();
        if (!isset($pats[$name])) {
            throw new \InvalidArgumentException(sprintf('Pat named "%s" doesn\'t exist', $name));
        }

        return $pats[$name];
    }

    /**
     * @return string
     */
    public static function getRandomPatName()
    {
        $pats = self::getPats();

        return array_rand($pats);
    }
}
