<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Gush\Application;
use Gush\Template\Meta\Header as TemplateHeader;
use Gush\Template\Pats\PatTemplate;
use Gush\Template\PullRequest\Create as PRCreate;
use Gush\Template\TemplateInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;

class TemplateHelper extends Helper implements InputAwareInterface
{
    /**
     * @var array
     */
    private $templates = [];

    /**
     * @var StyleHelper
     */
    private $style;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var Application
     */
    private $application;

    public function __construct(StyleHelper $style, Application $application)
    {
        $this->registerTemplate(new PRCreate\SymfonyTemplate());
        $this->registerTemplate(new PRCreate\SymfonyDocTemplate());
        $this->registerTemplate(new PRCreate\EnterpriseTemplate());
        $this->registerTemplate(new PRCreate\PullRequestCustomTemplate($application));
        $this->registerTemplate(new PatTemplate());
        $this->registerTemplate(new PRCreate\DefaultTemplate());
        $this->registerTemplate(new PRCreate\ZendFrameworkDocTemplate());
        $this->registerTemplate(new PRCreate\ZendFrameworkTemplate());
        $this->registerTemplate(new TemplateHeader\MITTemplate());
        $this->registerTemplate(new TemplateHeader\GPL3Template());
        $this->registerTemplate(new TemplateHeader\NoLicenseTemplate());
        $this->application = $application;
        $this->style = $style;
    }

    /**
     * @return null|string
     */
    public function getCustomTemplate($domain)
    {
        $config = $this->application->getConfig();

        if ('pull-request-create' === $domain &&  null !== $config && $config->has('table-pr')) {
            return 'custom';
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Registers a template.
     *
     * @param TemplateInterface $template
     *
     * @throws \InvalidArgumentException
     */
    public function registerTemplate(TemplateInterface $template)
    {
        $templateName = $template->getName();
        $parts = explode('/', $templateName);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Template name "%s" is not formatted properly, should be like "domain/template-name"',
                    $templateName
                )
            );
        }

        list($domain, $name) = $parts;

        $this->templates[$domain][$name] = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'template';
    }

    /**
     * Retrieves a template by domain and name.
     *
     * @param string $domain Domain of the template
     * @param string $name   Name of the template
     *
     * @throws \InvalidArgumentException
     *
     * @return TemplateInterface
     */
    public function getTemplate($domain, $name)
    {
        if (!isset($this->templates[$domain][$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Template with name "%s" in domain "%s" has not been registered',
                $name,
                $domain
            ));
        }

        return $this->templates[$domain][$name];
    }

    /**
     * Retrieves the requirements of the given template and asks the
     * user for any parameters that are not available from the
     * input, then binds the parameters to the template.
     *
     * @param TemplateInterface $template Template to render
     *
     * @internal param OutputInterface $output Output from the command
     */
    public function parameterize(TemplateInterface $template)
    {
        $params = [];

        foreach ($template->getRequirements() as $key => $requirement) {
            if (!$this->input->hasOption($key) || !$this->input->getOption($key)) {
                list($prompt, $default) = $requirement;

                if ('description' === $key) {
                    $v = $this->style->ask($prompt.' (enter "e" to open editor)', $default);

                    if ('e' === $v) {
                        $editor = $this->getHelperSet()->get('editor');
                        $v = $editor->fromString('');
                    }
                } else {
                    $v = $this->style->ask($prompt.' ', $default);
                }
            } else {
                $v = $this->input->getOption($key);
            }

            $params[$key] = $v;
        }

        $template->bind($params);
    }

    /**
     * Asks and renders will render the template.
     *
     * If any requirements are missing from the Input it will demand the
     * parameters from the user.
     *
     * @param string $templateDomain Domain for the template, e.g. pull-request
     * @param string $templateName   Name of the template, e.g. symfony-doc
     *
     * @return string Rendered template string
     */
    public function askAndRender($templateDomain, $templateName)
    {
        $template = $this->getTemplate($templateDomain, $templateName);
        $this->parameterize($template);

        return $template->render();
    }

    /**
     * Like askAndRender but directly binding parameters passed
     * not as options but as direct input argument to method.
     *
     * @param array  $parameters
     * @param string $templateDomain Domain for the template, e.g. pull-request
     * @param string $templateName   Name of the template, e.g. symfony-doc
     *
     * @return string Rendered template string
     */
    public function bindAndRender(array $parameters, $templateDomain, $templateName)
    {
        $template = $this->getTemplate($templateDomain, $templateName);
        $template->bind($parameters);

        return $template->render();
    }

    /**
     * Returns the names of registered templates in the given domain.
     *
     * @param string $domain Return template names for this domain
     *
     * @throws \InvalidArgumentException
     *
     * @return array Array of template name strings
     */
    public function getNamesForDomain($domain)
    {
        if (!isset($this->templates[$domain])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown template domain "%s"',
                $domain
            ));
        }

        return array_keys($this->templates[$domain]);
    }
}
