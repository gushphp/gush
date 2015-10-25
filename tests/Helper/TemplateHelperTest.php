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

use Gush\Helper\StyleHelper;
use Gush\Helper\TemplateHelper;
use Gush\Tests\BaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;

class TemplateHelperTest extends BaseTestCase
{
    /**
     * @var \Gush\Helper\TemplateHelper
     */
    private $helper;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $template;

    /**
     * @var ArrayInput
     */
    private $input;

    /**
     * @var BufferedOutput
     */
    private $output;

    /**
     * @var \Gush\Application
     */
    private $application;

    public function setUp()
    {
        parent::setUp();

        $this->template = $this->prophesize('Gush\Template\TemplateInterface');

        $inputDef = new InputDefinition();
        $inputDef->addOption(new InputOption('test-option', null, InputOption::VALUE_OPTIONAL));

        $this->input = new ArrayInput(['--test-option' => null], $inputDef);
        $this->output = new BufferedOutput();

        $this->application = $this->getApplication();

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->application->getHelperSet()->get('gush_style');
        $styleHelper->setInput($this->input);
        $styleHelper->setOutput($this->output);

        $this->helper = new TemplateHelper($styleHelper, $this->application);
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
        $this->template->getName()->willReturn($name);

        if ($exception) {
            $this->setExpectedException('InvalidArgumentException');
        }

        $this->helper->registerTemplate($this->template->reveal());

        $res = $this->helper->getTemplate($parts[0], $parts[1]);

        $this->assertInstanceOf('Gush\Template\TemplateInterface', $res);
    }

    public function provideGetNamesForDomain()
    {
        return [
            [
                ['foo/bar', 'foo/bong', 'bar/boo'],
                'foo',
                ['bar', 'bong'],
            ],
            [
                ['foo/bar', 'foo/bong', 'bar/boo'],
                'bar',
                ['boo'],
            ],
            [
                [],
                'bar',
                [],
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideGetNamesForDomain
     */
    public function gets_names_for_domain($templateRegistrations, $domain, $expectedNames, $exception = false)
    {
        foreach ($templateRegistrations as $templateRegistration) {
            $template = $this->prophesize('Gush\Template\TemplateInterface');
            $template->getName()->willReturn($templateRegistration);

            $this->helper->registerTemplate($template->reveal());
        }

        if ($exception) {
            $this->setExpectedException('InvalidArgumentException', 'Unknown template domain');
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
            ['pats', 'general'],
        ];
    }

    /**
     * @test
     * @dataProvider provideGetHelper
     */
    public function gets_helper($domain, $name)
    {
        $res = $this->helper->getTemplate($domain, $name);

        $this->assertInstanceof('Gush\Template\TemplateInterface', $res);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage has not been registered
     */
    public function errors_when_getting_wrong_helper()
    {
        $this->helper->getTemplate('foobar', 'bar-foo');
    }

    public function provideParameterize()
    {
        return [
            [
                ['foo' => ['This is foo', 'default-bar']],
                ['foo' => 'foo', 'test-option' => 'test-option'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideParameterize
     */
    public function parameterizes($requirements, $bindArguments)
    {
        $requirements['test-option'] = ['This is bar', 'default-foo'];

        $this->input->setOption('test-option', 'test-option');

        $this->template->getName()->willReturn('test/foobar')->shouldBeCalled();
        $this->template->getRequirements()->willReturn($requirements)->shouldBeCalled();
        $this->template->bind($bindArguments)->shouldBeCalled();
        $this->template->render()->willReturn('foo')->shouldBeCalled();

        // // less one because we test with one given option
        $this->setExpectedApplicationInput(array_fill(0, count($requirements) - 1, 'foo'));

        $this->helper->registerTemplate($this->template->reveal());
        $res = $this->helper->askAndRender('test', 'foobar');

        $this->assertEquals('foo', $res);
    }

    /**
     * @test
     */
    public function binds_and_renders()
    {
        $this->input->setOption('test-option', 'test-option');

        $this->template->getName()->willReturn('test/foobar')->shouldBeCalled();
        $this->template->bind(['author' => 'cslucano'])->shouldBeCalled();
        $this->template->render()->willReturn('foo')->shouldBeCalled();

        $this->helper->registerTemplate($this->template->reveal());
        $res = $this->helper->bindAndRender(['author' => 'cslucano'], 'test', 'foobar');

        $this->assertEquals('foo', $res);
    }

    /**
     * @param array|string $input
     */
    private function setExpectedApplicationInput($input)
    {
        if (is_array($input)) {
            $input = implode("\n", $input);
        }

        $helper = $this->application->getHelperSet()->get('gush_question');
        $helper->setInputStream($this->getInputStream($input));
    }

    private function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }
}
