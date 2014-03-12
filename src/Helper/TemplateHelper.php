<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Gush\Template\Pats\PatTemplate;
use Gush\Template\PullRequest\Create\DefaultTemplate;
use Gush\Template\PullRequest\Create\SymfonyDocTemplate;
use Gush\Template\PullRequest\Create\SymfonyTemplate;
use Gush\Template\TemplateInterface;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\Helper;

class TemplateHelper extends Helper implements InputAwareInterface
{
    protected $templates;
    protected $dialog;
    protected $input;

    public function __construct(DialogHelper $dialog)
    {
        // for the moment we register a set of default Gush templates
        $this->registerTemplate(new SymfonyTemplate());
        $this->registerTemplate(new SymfonyDocTemplate());
        $this->registerTemplate(new PatTemplate());
        $this->registerTemplate(new DefaultTemplate());
        $this->dialog = $dialog;
    }

    /**
     * {@inheritDoc}
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
                $v = $this->dialog->ask($output, $prompt . ' ', $default);
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
     * @param OutputInterface $output Output from command
     * @param string                   Domain for the template, e.g. pull-request
     * @param string                   Name of the template, e.g. symfony-doc
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
     * @param array   $parameters
     * @param string  $templateDomain  Domain for the template, e.g. pull-request
     * @param string  $templateName    Name of the template, e.g. symfony-doc
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
