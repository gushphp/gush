<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\TextHelper;

class TextHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TextHelper
     */
    protected $textHelper;

    public function setUp()
    {
        $this->textHelper = new TextHelper();
    }

    public function provideTruncate()
    {
        return [
            [
                'this is some text',
                5,
                null,
                null,
                'th...',
            ],
            [
                'this is some text',
                5,
                'right',
                null,
                '...xt',
            ],
            [
                'this is some text',
                5,
                'right',
                '-',
                '-text',
            ],
            [
                'th',
                5,
                'right',
                '-',
                'th',
            ],
            [
                'this is some more text',
                5,
                'right',
                '-----',
                '-----',
            ],
            [
                'this is some more text',
                5,
                'right',
                '--------',
                '-----',
                'Delimiter length "8" cannot be greater',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideTruncate
     */
    public function truncates($text, $length, $alignment, $truncateString, $expected, $expectedException = null)
    {
        if ($expectedException) {
            $this->setExpectedException('InvalidArgumentException', $expectedException);
        }
        $res = $this->textHelper->truncate($text, $length, $alignment, $truncateString);
        $this->assertEquals($expected, $res);
    }

    public function provideSlugify()
    {
        return [
            ['this is some text', 'this-is-some-text'],
            ['voilà, j\'ai du texte', 'voila-j-ai-du-texte'],
            ['áçéeë', 'aceee'],
        ];
    }

    /**
     * @test
     * @dataProvider provideSlugify
     */
    public function slugifies($string, $expected)
    {
        $string = $this->textHelper->slugify($string);
        $this->assertEquals($expected, $string);
    }
}
