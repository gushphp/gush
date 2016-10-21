<?php

declare(strict_types = 1);

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Gush\Config;
use Symfony\Component\Console\Helper\Helper;

class TemplateRenderHelper extends Helper
{
    const TYPE_STRING = 'string';
    const TYPE_FILE = 'file';

    /**
     * @var \Closure
     */
    private $twigServiceLoader;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Twig_Environment|null
     */
    private $twig;

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'template_render';
    }

    public function __construct(\Closure $twigServiceLoader, Config $config)
    {
        $this->twigServiceLoader = $twigServiceLoader;
        $this->config = $config;
    }

    /**
     * Tries to find the template by name, and fallback to filename (system default).
     *
     * This method first looks if a template was configured in the local
     * configuration, then fallback to the filename if none was configured.
     *
     * A local template is a string, rendered as-is.
     *
     * @param string      $templateName
     * @param string|null $filename
     *
     * @return string The full location of the template.
     */
    public function findTemplate(string $templateName, string $filename = null): array
    {
        if (null === ($template = $this->config->get(['templates', $templateName], Config::CONFIG_LOCAL))) {
            return ['@gush/'.$filename, self::TYPE_FILE];
        }

        return [$template, self::TYPE_STRING];
    }

    /**
     * @param array $template [template filename/string, type]
     * @param array $vars     Variables for the template
     *
     * @return string
     */
    public function renderTemplate(array $template, array $vars): string
    {
        if (self::TYPE_STRING === $template[1]) {
            return $this->getTwig()->createTemplate($template[0])->render($vars);
        }

        return $this->getTwig()->render($template[0], $vars);
    }

    private function getTwig(): \Twig_Environment
    {
        if (null === $this->twig) {
            $this->twig = ($this->twigServiceLoader)();
        }

        return $this->twig;
    }
}
