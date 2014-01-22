<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\Helper;

class TextHelper extends Helper
{
    public function getName()
    {
        return 'text';
    }

    public function truncate($string, $length, $alignment = null, $delimString = null)
    {
        $alignment = $alignment === null ? 'left' : $alignment;
        $delimString = $delimString === null ? '...' : $delimString;
        $delimLen = strlen($delimString);

        if (!in_array($alignment, ['left', 'right'])) {
            throw new \InvalidArgumentException(
                'Alignment must either be "left" or "right"'
            );
        }

        if ($delimLen > $length) {
            throw new \InvalidArgumentException(sprintf(
                'Delimiter length "%s" cannot be greater than truncate length "%s"',
                $delimLen, $length
            ));
        }

        if (strlen($string) > $length) {
            $offset = $length - $delimLen;
            if ('left' === $alignment) {
                $string = substr($string, 0, $offset) . $delimString;
            } else {
                $string = $delimString . substr($string, 
                    strlen($string) - $offset
                );
            }
        }

        return $string;
    }
}
