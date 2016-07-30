<?php

/*
 * This file is part of Gush package.
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

    /**
     * @var PatTemplate
     */
    protected $template;

    public function setUp()
    {
        $this->template = new PatTemplate();
    }

    /**
     * @test
     */
    public function renders_string_with_placeholders_filled()
    {
        $this->template->bind(['author' => self::TEST_AUTHOR, 'pat' => 'thank_you']);

        $this->assertContains(
            self::TEST_AUTHOR,
            $this->template->render()
        );
    }

    public function testWrongPatName()
    {
        $this->template->bind(['author' => self::TEST_AUTHOR, 'pat' => 'nonexistent']);

        $this->setExpectedException('\InvalidArgumentException', 'Pat named "nonexistent" doesn\'t exist');
        $this->template->render();
    }
}
