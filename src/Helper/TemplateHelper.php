<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Gush\Application;
use Gush\Template\Meta\Header\GPL3Template;
use Gush\Template\Meta\Header\MITTemplate;
use Gush\Template\Meta\Header\NoLicenseTemplate;
use Gush\Template\Pats\PatTemplate;
use Gush\Template\PullRequest\Create\DefaultTemplate;
use Gush\Template\PullRequest\Create\EnterpriseTemplate;
use Gush\Template\PullRequest\Create\PullRequestCustomTemplate;
use Gush\Template\PullRequest\Create\SymfonyDocTemplate;
use Gush\Template\PullRequest\Create\SymfonyTemplate;
use Gush\Template\PullRequest\Create\ZendFrameworkDocTemplate;
use Gush\Template\PullRequest\Create\ZendFrameworkTemplate;
use Gush\Template\TemplateInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Question\Question;

class TemplateHelper extends Helper implements InputAwareInterface
{
    protected $templates;
    /** @var QuestionHelper  */
    protected $questionHelper;
    /** @var  InputInterface */
    protected $input;
    /** @var Application */
    private $application;

    public function __construct(QuestionHelper $questionHelper, Application $application)
    {
        $this->registerTemplate(new SymfonyTemplate());
        $this->registerTemplate(new SymfonyDocTemplate());
        $this->registerTemplate(new EnterpriseTemplate());
        $this->registerTemplate(new PullRequestCustomTemplate($application));
        $this->registerTemplate(new PatTemplate());
        $this->registerTemplate(new DefaultTemplate());
        $this->registerTemplate(new ZendFrameworkDocTemplate());
        $this->registerTemplate(new ZendFrameworkTemplate());
        $this->registerTemplate(new MITTemplate());
        $this->registerTemplate(new GPL3Template());
        $this->registerTemplate(new NoLicenseTemplate());
        $this->questionHelper = $questionHelper;
        $this->application = $application;
    }

    /**
     * @return null|string
     */
    public function getCustomTemplate()
    {
        if ($this->application->getConfig()->has('table-pr')) {
            return 'custom';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Registers a template
     *
     * @param \Gush\Template\TemplateInterface $template
     *
     * @throws \InvalidArgumentException
     */
    public function registerTemplate(TemplateInterface $template)
    {
        $templateName = $template->getName();
        $parts = explode('/', $templateName);

        if (count($parts) != 2) {
            throw new \InvalidArgumentException(sprintf(
                'Template name "%s" is not formatted properly, should be like "domain/template-name"',
                $templateName
            ));
        }

        list($domain, $name) = $parts;
        $this->templates[$domain][$name] = $template;
    }

    public function getName()
    {
        return 'template';
    }

    /**
     * Retrieves a template
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
     * @param \Symfony\Component\Console\Output\OutputInterface    Output from the command
     * @param \Gush\Template\TemplateInterface  Template to render
     */
    public function parameterize(OutputInterface $output, TemplateInterface $template)
    {
        $params = [];
        foreach ($template->getRequirements() as $key => $requirement) {
            if (!$this->input->hasOption($key) || !$this->input->getOption($key)) {
                list($prompt, $default) = $requirement;
                $prompt  = $default ? $prompt . ' (' . $default . ')' : $prompt;

                if ('description' === $key) {
                    $prompt .= ' (enter "e" to open editor)';

                    $v = $this->questionHelper->ask($this->input, $output, new Question($prompt.' ', $default));

                    if ('e' === $v) {
                        $editor = $this->getHelperSet()->get('editor');

                        $v = $editor->fromString('');
                    }
                } else {
                    $v = $this->questionHelper->ask($this->input, $output, new Question($prompt.' ', $default));
                }
            } else {
                $v = $this->input->getOption($key);
            }

            $params[$key] = $v;
        }

        $template->bind($params);
    }

    /**
     * Asks and renders will render the template. If any requirements
     * are missing from the Input it will demand the parameters from
     * the user.
     *
     * @param OutputInterface $output         Output from command
     * @param string          $templateDomain Domain for the template, e.g. pull-request
     * @param string          $templateName   Name of the template, e.g. symfony-doc
     *
     * @return string Rendered template string
     */
    public function askAndRender(OutputInterface $output, $templateDomain, $templateName)
    {
        $template = $this->getTemplate($templateDomain, $templateName);
        $this->parameterize($output, $template);

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
     * Returns the names of registered templates in the given
     * domain.
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
