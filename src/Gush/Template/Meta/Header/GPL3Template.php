<?php

namespace Gush\Template\Meta\Header;

use Gush\Template\AbstractTemplate;

class GPL3Template extends AbstractTemplate
{
    protected $header = <<<EOT
This file is part of {{ package_name }}.

{{ package_name }} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

{{ package_name }} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with {{ package_name }}.  If not, see <http://www.gnu.org/licenses/>.
EOT;

    public function getName()
    {
        return 'meta-header/gpl3';
    }

    public function getRequirements()
    {
        return [
            'package_name' => ['Package Name?:', 'Your Package'],
        ];
    }

    public function render()
    {
        $params = array_merge(array(
            'copyright_to' => date('Y')
        ), $this->parameters);

        return $this->replaceTokens($this->header, $params);
    }
}

