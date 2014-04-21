<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\Meta\Header;

use Gush\Template\AbstractTemplate;

class NoLicenseTemplate extends AbstractTemplate
{
    /**
     * @var string
     */
    protected $header = <<<EOT
Copyright {{ copyright-year }} {{ copyright-holder }}

Distribution and reproduction are prohibited.

@package     {{ package-name }}
@copyright   {{ copyright-holder }} {{ copyright-year }}
@license     No License (Proprietary)
EOT;

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'meta-header/no-license';
    }

    /**
     * {@inheritDoc}
     */
    public function getRequirements()
    {
        return [
            'package-name' => ['Package Name?:', 'Your Package'],
            'copyright-holder' => ['Copyright Holder:', 'Company Name'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $params = array_merge(['copyright-year' => date('Y')], $this->parameters);

        return $this->replaceTokens($this->header, $params);
    }
}
