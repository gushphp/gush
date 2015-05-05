<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Template\Meta\Header;

use Gush\Template\AbstractTemplate;

class MITTemplate extends AbstractTemplate
{
    /**
     * @var string
     */
    protected $header = <<<EOT
This file is part of {{ package-name }} package.

(c) {{ copyright-from }}-{{ copyright-to }} {{ copyright-holder }}

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOT;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'meta-header/mit';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return [
            'package-name' => ['Package Name', 'Your Package'],
            'copyright-holder' => ['Copyright Holder', 'You <you@yourdomain.com>'],
            'copyright-from' => ['Copyright Starts From', '2009'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $params = array_merge(['copyright-to' => date('Y')], $this->parameters);

        return $this->replaceTokens($this->header, $params);
    }
}
