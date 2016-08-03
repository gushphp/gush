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
|Bug fix?     |n  |
|New feature? |n  |
|BC breaks?   |n  |
|Deprecations?|n  |
|Tests pass?  |n  |
|Fixed tickets|   |
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
|Bug fix?     |y     |
|New feature? |yes   |
|BC breaks?   |yes   |
|Deprecations?|yes   |
|Tests pass?  |yes   |
|Fixed tickets|none  |
|License      |Apache|


This is a description
EOF
            ],
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
            'bug_fix' => ['Bug fix?', 'n'],
            'new_feature' => ['New feature?', 'n'],
            'bc_breaks' => ['BC breaks?', 'n'],
            'deprecations' => ['Deprecations?', 'n'],
            'tests_pass' => ['Tests pass?', 'n'],
            'fixed_tickets' => ['Fixed tickets', ''],
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
                0 => ['Bug fix?', 'n'],
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
                'new_feature' => ['Bug fix?', 'no'],
                'bug_fix' => ['Bug fix?'],
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
            [],
            '/data/repo-dir',
            ['table-pr' => $requirements]
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
