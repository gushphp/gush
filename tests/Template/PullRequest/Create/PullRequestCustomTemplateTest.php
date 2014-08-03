<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Template\PullRequest\Create;

use Gush\Config;
use Gush\Template\PullRequest\Create\PullRequestCustomTemplate;

class PullRequestCustomTemplateTest extends \PHPUnit_Framework_TestCase
{
    /** @var PullRequestCustomTemplate */
    protected $template;

    /**
     * @var \Gush\Application|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $application;

    public function setUp()
    {
        $this->application = $this->getMockBuilder('Gush\Application')
            ->disableOriginalConstructor()
            ->setMethods(['getConfig'])
            ->getMock()
        ;

        $this->template = new PullRequestCustomTemplate($this->application);
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
     * @test
     * @dataProvider provideTemplate
     */
    public function runs_template_command($params, $expected)
    {
        $table = [
            'bug_fix' => ['Bug Fix?', 'n'],
            'new_feature' => ['New Feature?', 'n'],
            'bc_breaks' => ['BC Breaks?', 'n'],
            'deprecations' => ['Deprecations?', 'n'],
            'tests_pass' => ['Tests Pass?', 'n'],
            'fixed_tickets' => ['Fixed Tickets', ''],
            'license' => ['License', 'MIT'],
            'description' => ['Description', ''],
        ];

        $config = new Config();
        $config->merge([
            'table-pr' => $table
        ]);

        $this->application
            ->expects($this->atLeastOnce())
            ->method('getConfig')
            ->will($this->returnValue($config))
        ;

        $requirements = $this->template->getRequirements();

        foreach ($requirements as $key => $requirement) {
            list (, $default) = $requirement;
            if (!isset($params[$key])) {
                $params[$key] = $default;
            }
        }

        $params['description'] = 'This is a description';

        $this->template->bind($params);
        $res = $this->template->render();

        $this->assertEquals(self::normalizeWhiteSpace($expected), self::normalizeWhiteSpace($res));
    }

    /**
     * @test
     */
    public function errors_when_table_is_empty()
    {
        $config = new Config();
        $config->merge([
            'table-pr' => []
        ]);

        $this->application
            ->expects($this->atLeastOnce())
            ->method('getConfig')
            ->will($this->returnValue($config))
        ;

        $this->setExpectedException(
            'RuntimeException',
            'table-pr structure requires at least one row, please check your local .gush.yml'
        );

        $this->template->getRequirements();
    }

    /**
     * @test
     */
    public function errors_with_name_not_a_string()
    {
        $config = new Config();
        $config->merge([
            'table-pr' => [
                0 => ['Bug Fix?', 'n'],
            ]
        ]);

        $this->application
            ->expects($this->atLeastOnce())
            ->method('getConfig')
            ->will($this->returnValue($config))
        ;

        $this->setExpectedException(
            'RuntimeException',
            'table-pr table row-name must be a string, please check your local .gush.yml'
        );

        $this->template->getRequirements();
    }

    /**
     * @test
     */
    public function errors_with_invalid_row()
    {
        $config = new \Gush\Config();
        $config->merge([
            'table-pr' => [
                'new_feature' => ['Bug Fix?', 'no'],
                'bug_fix' => ['Bug Fix?'],
            ]
        ]);

        $this->application
            ->expects($this->atLeastOnce())
            ->method('getConfig')
            ->will($this->returnValue($config))
        ;

        $this->setExpectedException(
            'RuntimeException',
            'table-pr table row-data "bug_fix" must be an array with exactly two values like: [Label, default value].'
        );

        $this->template->getRequirements();
    }

    private static function normalizeWhiteSpace($input)
    {
        $input = str_replace("\r\n", "\n", $input);
        $input = str_replace("\r", "\n", $input);
        $input = preg_replace('/^\s+$/m', '', $input);

        return $input;
    }
}
