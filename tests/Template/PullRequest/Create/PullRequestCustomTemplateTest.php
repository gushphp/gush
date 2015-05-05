<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Template\PullRequest\Create;

use Gush\Config;
use Gush\Template\PullRequest\Create\PullRequestCustomTemplate;
use Gush\Tests\BaseTestCase;

class PullRequestCustomTemplateTest extends BaseTestCase
{
    public static function provideTemplate()
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


This is a description
EOF
            ]
        ];
    }

    /**
     * @dataProvider provideTemplate
     *
     * @param array  $params
     * @param string $expected
     */
    public function testRenderTemplate(array $params, $expected)
    {
        $table = [
            'bug_fix' => ['Bug Fix?', 'n'],
            'new_feature' => ['New Feature?', 'n'],
            'bc_breaks' => ['BC Breaks?', 'n'],
            'deprecations' => ['Deprecations?', 'n'],
            'tests_pass' => ['Tests Pass?', 'n'],
            'fixed_tickets' => ['Fixed Tickets', ''],
            'license' => ['License', 'MIT'],
        ];

        $template = $this->getCustomTemplate($table);
        $requirements = $template->getRequirements();

        foreach ($requirements as $key => $requirement) {
            list(, $default) = $requirement;

            if (!isset($params[$key])) {
                $params[$key] = $default;
            }
        }

        $params['description'] = 'This is a description';

        $template->bind($params);
        $res = $template->render();

        $this->assertEquals(self::normalizeWhiteSpace($expected), self::normalizeWhiteSpace($res));
    }

    public function testThrowsErrorWhenTableIsEmpty()
    {
        $template = $this->getCustomTemplate([]);

        $this->setExpectedException(
            'RuntimeException',
            'table-pr structure requires at least one row'
        );

        $template->getRequirements();
    }

    /**
     * @test
     */
    public function testThrowsExceptionWhenNameIsNoString()
    {
        $template = $this->getCustomTemplate(
            [
                0 => ['Bug Fix?', 'n'],
            ]
        );

        $this->setExpectedException(
            'RuntimeException',
            'table-pr table row-name must be a string'
        );

        $template->getRequirements();
    }

    /**
     * @test
     */
    public function testThrowsExceptionWhenRowIsInvalid()
    {
        $template = $this->getCustomTemplate(
            [
                'new_feature' => ['Bug Fix?', 'no'],
                'bug_fix' => ['Bug Fix?'],
            ]
        );

        $this->setExpectedException(
            'RuntimeException',
            'table-pr table row-data "bug_fix" must be an array with at least two values like: [Label, default value].'
        );

        $template->getRequirements();
    }

    private function getCustomTemplate(array $requirements)
    {
        $config = new Config(
            '/home/user',
            '/temp/gush',
            [],
            '/data/repo-dir',
            ['table-pr' => $requirements ]
        );

        return new PullRequestCustomTemplate($this->getApplication($config));
    }

    private static function normalizeWhiteSpace($input)
    {
        $input = str_replace(PHP_EOL, "\n", $input);
        $input = preg_replace('/^\s+$/m', '', $input);

        return $input;
    }
}
