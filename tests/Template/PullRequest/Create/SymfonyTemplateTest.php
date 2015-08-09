<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Template\PullRequest\Create;

use Gush\Template\PullRequest\Create\SymfonyTemplate;

class SymfonyTemplateTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Gush\Template\PullRequest\Create\SymfonyTemplate */
    protected $template;

    public function setUp()
    {
        $this->template = new SymfonyTemplate();
    }

    public function provideTemplate()
    {
        return [
            [
                [],
                <<<EOF

|Q            |A  |
|---          |---|
|Bug Fix?     |n  |
|New Feature? |n  |
|BC Breaks?   |n  |
|Deprecations?|n  |
|Tests Pass?  |n  |
|Fixed Tickets|   |
|License      |MIT|
|Doc PR       |   |


This is a description
EOF
            ],
            [
                [
                    'bug_fix' => 'y',
                    'new_feature' => 'yes',
                    'bc_breaks' => 'yes',
                    'deprecations' => 'yes',
                    'tests_pass' => 'yes',
                    'fixed_tickets' => 'none',
                    'license' => 'Apache',
                    'doc_pr' => 'none',
                ],
                <<<EOF

|Q            |A     |
|---          |---   |
|Bug Fix?     |y     |
|New Feature? |yes   |
|BC Breaks?   |yes   |
|Deprecations?|yes   |
|Tests Pass?  |yes   |
|Fixed Tickets|none  |
|License      |Apache|
|Doc PR       |none  |


This is a description
EOF
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideTemplate
     */
    public function runs_template_command_with_symfony_template($params, $expected)
    {
        $requirements = $this->template->getRequirements();

        foreach ($requirements as $key => $requirement) {
            list($prompt, $default) = $requirement;
            if (!isset($params[$key])) {
                $params[$key] = $default;
            }
        }

        $params['description'] = 'This is a description';

        $this->template->bind($params);
        $res = $this->template->render();

        $this->assertEquals(self::normalizeWhiteSpace($expected), self::normalizeWhiteSpace($res));
    }

    private static function normalizeWhiteSpace($input)
    {
        $input = str_replace("\r\n", "\n", $input);
        $input = str_replace("\r", "\n", $input);
        $input = preg_replace('/^\s+$/m', '', $input);

        return $input;
    }
}
