<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Template\Pats;

use Gush\Template\Pats\PatTemplate;

class PatTemplateTest extends \PHPUnit_Framework_TestCase
{
    const TEST_AUTHOR = 'cslucano';

    /** @var \Gush\Template\Pats\PatTemplate */
    protected $template;

    public function setUp()
    {
        $this->template = new PatTemplate();
    }

    /**
     * @test
     */
    public function it_renders_string_with_placeholders_replaced()
    {
        $this->template->bind(['author' => self::TEST_AUTHOR]);

        $this->assertContains(
            self::TEST_AUTHOR,
            $this->template->render()
        );
    }
}
