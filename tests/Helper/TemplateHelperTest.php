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

use Gush\Helper\TemplateHelper;

class TemplateHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Gush\Helper\TemplateHelper */
    protected $helper;
    protected $template;
    protected $questionHelper;
    protected $output;
    protected $input;

    public function setUp()
    {
        $this->questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $this->output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $this->input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $this->template = $this->getMock('Gush\Template\TemplateInterface');

        $application = $this->getMockBuilder('Gush\Application')
            ->disableOriginalConstructor()
            ->setMethods(['getConfig'])
            ->getMock()
        ;

        $this->helper = new TemplateHelper($this->questionHelper, $application);
        $this->helper->setInput($this->input);
    }

    public function provideRegisterTemplate()
    {
        return [
            ['foobar', null, true],
            ['', null, true],
            ['foo/bar/far', null, true],
            ['foo/bar', ['foo', 'bar']],
        ];
    }

    /**
     * @test
     * @dataProvider provideRegisterTemplate
     */
    public function registers_template($name, $parts, $exception = false)
    {
        if (true === $exception) {
            $this->setExpectedException('InvalidArgumentException');
        }

        $this->template->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name))
        ;
        $this->helper->registerTemplate($this->template);
        $res = $this->helper->getTemplate($parts[0], $parts[1]);
        $this->assertInstanceOf('Gush\Template\TemplateInterface', $res);
    }

    public function provideGetNamesForDomain()
    {
        return [
            [
                ['foo/bar', 'foo/bong', 'bar/boo'], 'foo', ['bar', 'bong'],
            ],
            [
                ['foo/bar', 'foo/bong', 'bar/boo' ], 'bar', ['boo'],
            ],
            [
                [], 'bar', [], true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideGetNamesForDomain
     * @depends testRegisterTemplate
     */
    public function gets_names_for_domain($templateRegistrations, $domain, $expectedNames, $exception = false)
    {
        if (true === $exception) {
            $this->setExpectedException('InvalidArgumentException', 'Unknown template domain');
        }

        foreach ($templateRegistrations as $templateRegistration) {
            $template = $this->getMock('Gush\Template\TemplateInterface');
            $template->expects($this->once())
                ->method('getName')
                ->will($this->returnValue($templateRegistration))
            ;
            $this->helper->registerTemplate($template);
        }

        $res = $this->helper->getNamesForDomain($domain);
        $this->assertEquals($expectedNames, $res);
    }

    public function provideGetHelper()
    {
        return [
            ['pull-request-create', 'default'],
            ['pull-request-create', 'symfony'],
            ['pull-request-create', 'symfony-doc'],
            ['pats', 'general']
        ];
    }

    /**
     * @test
     * @dataProvider provideGetHelper
     */
    public function gets_helper($domain, $name)
    {
        $res = $this->helper->getTemplate($domain, $name);
        $this->assertNotNull($res);
        $this->assertInstanceof('Gush\Template\TemplateInterface', $res);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage has not been registered
     */
    public function backfires_when_getting_wrong_helper()
    {
        $this->helper->getTemplate('foobar', 'barfoo');
    }

    public function provideParameterize()
    {
        return [
            [[
                'foo' => ['This is foo', 'default-bar'],
            ]]
        ];
    }

    /**
     * @test
     * @dataProvider provideParameterize
     */
    public function parameterizes($requirements)
    {
        $requirements['test-option'] = ['This is bar', 'default-foo'];

        $this->template->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test/foobar'));

        $this->input->expects($this->any())
            ->method('hasOption')
            ->will($this->returnCallback(function ($option) {
                if ($option == 'test-option') {
                    return true;
                }
            }))
        ;

        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function ($option) {
                if ($option == 'test-option') {
                    return 'test-option';
                }
            }));

        $this->template->expects($this->once())
            ->method('getRequirements')
            ->will($this->returnValue($requirements));

        // less one because we test with one given option
        $this->questionHelper->expects($this->exactly(count($requirements) - 1))
            ->method('ask')
            ->will($this->returnValue('foo'));

        $this->template->expects($this->once())
            ->method('bind');

        $this->template->expects($this->once())
            ->method('render')
            ->will($this->returnValue('foo'));

        $this->helper->registerTemplate($this->template);

        $res = $this->helper->askAndRender($this->output, 'test', 'foobar');
        $this->assertEquals('foo', $res);
    }

    /**
     * @test
     */
    public function binds_and_renders()
    {
        $this->template->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test/foobar'))
        ;

        $this->input->expects($this->any())
            ->method('hasOption')
            ->will($this->returnCallback(function ($option) {
                if ($option == 'test-option') {
                    return true;
                }
            }))
        ;

        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function ($option) {
                if ($option == 'test-option') {
                    return 'test-option';
                }
            }))
        ;

        $this->template->expects($this->once())
            ->method('bind')
        ;

        $this->template->expects($this->once())
            ->method('render')
            ->will($this->returnValue('foo'))
        ;

        $this->helper->registerTemplate($this->template);

        $res = $this->helper->bindAndRender(['author' => 'cslucano'], 'test', 'foobar');
        $this->assertEquals('foo', $res);
    }
}
