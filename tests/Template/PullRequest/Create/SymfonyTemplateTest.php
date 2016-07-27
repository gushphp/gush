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

|Q            |A     |
|---          |---   |
|Branch       |master|
|Bug fix?     |no    |
|New feature? |no    |
|BC breaks?   |no    |
|Deprecations?|no    |
|Tests pass?  |yes   |
|Fixed tickets|      |
|License      |MIT   |
|Doc PR       |      |


This is a description
EOF
            ],
            [
                [
                    'branch' => 'master',
                    'bug_fix' => 'yes',
                    'new_feature' => 'yes',
                    'bc_breaks' => 'yes',
                    'deprecations' => 'yes',
                    'tests_pass' => 'no',
                    'fixed_tickets' => 'none',
                    'license' => 'Apache',
                    'doc_pr' => 'none',
                ],
                <<<EOF

|Q            |A     |
|---          |---   |
|Branch       |master|
|Bug fix?     |yes   |
|New feature? |yes   |
|BC breaks?   |yes   |
|Deprecations?|yes   |
|Tests pass?  |no    |
|Fixed tickets|none  |
|License      |Apache|
|Doc PR       |none  |


This is a description
EOF
            ],
            [
                [
                    'branch' => 'master',
                    'bug_fix' => 'yes|no',
                    'new_feature' => 'yes|no',
                    'bc_breaks' => 'kinda|yes|no',
                    'deprecations' => 'yes|no',
                    'tests_pass' => 'no|yes|maybe',
                    'fixed_tickets' => 'none|some',
                    'license' => 'BSD',
                    'doc_pr' => 'none',
                ],
                <<<EOF

|Q            |A     |
|---          |---   |
|Branch       |master|
|Bug fix?     |yes   |
|New feature? |yes   |
|BC breaks?   |kinda |
|Deprecations?|yes   |
|Tests pass?  |no    |
|Fixed tickets|none  |
|License      |BSD   |
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
                if (1 < count($choices = explode('|', $default))) {
                    $default = $choices[0];
                }
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
