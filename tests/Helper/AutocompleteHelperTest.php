<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\AutocompleteHelper;
use Gush\Tests\Fixtures\OutputFixtures;

class AutocompleteHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AutocompleteHelper
     */
    protected $autocompleteHelper;

    public function setUp()
    {
        $this->autocompleteHelper = new AutocompleteHelper();
    }

    /**
     * @test
     */
    public function gets_autocomplete_script()
    {
        $commands = [
            [
                'name' => 'test:command',
                'definition' => [
                    'options' => [
                        'stable' => ['name' => "--stable"],
                        'org' => ['name' => "--org"]
                    ]
                ]
            ]
        ];

        $string = $this->autocompleteHelper->getAutoCompleteScript($commands);
        $this->assertEquals(OutputFixtures::AUTOCOMPLETE_SCRIPT, $string);
    }
}
