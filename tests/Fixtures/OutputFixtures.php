<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Fixtures;

/**
 * Please do not auto-edit this file thereby removing intentional white spaces.
 */
class OutputFixtures
{
    const HEADER_LICENSE_TWIG = <<<EOT
{#
 # This file is part of Your Package package.
 #
 # (c) 2009-2016 You <you@yourdomain.com>
 #
 # This source file is subject to the MIT license that is bundled
 # with this source code in the file LICENSE.
 #}

{% extends "base.twig" %}

{% block myBody %}
    <div class="someDiv">
        Some Content
    </div>
{% endblock myBody %}

EOT;

    const HEADER_LICENSE_PHP = <<<EOT
<?php

/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2016 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Test;

class MetaTest
{
    private \$test;

    public function __construct(\$test)
    {
        \$this->test = \$test;
    }
}

EOT;

    const HEADER_LICENSE_JS = <<<EOT
/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2016 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function ($) {
    $.fn.someFunction = function () {
        return $(this).append('New Function');
    };
})(window.jQuery);

EOT;

    const HEADER_LICENSE_CSS = <<<EOT
/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2016 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

.someDiv {
    background: #ff0000;
}

a#someId {
    color: #000000;
}

EOT;

    const AUTOCOMPLETE_SCRIPT = <<<EOT
#!/bin/sh
_gush()
{
    local cur prev coms opts
    COMPREPLY=()
    cur="\${COMP_WORDS[COMP_CWORD]}"
    prev="\${COMP_WORDS[COMP_CWORD-1]}"
    coms="test:command"
    opts="--stable --org"

    if [[ \${COMP_CWORD} = 1 ]] ; then
        COMPREPLY=($(compgen -W "\${coms}" -- \${cur}))

        return 0
    fi

    case "\${prev}" in

        esac

    COMPREPLY=($(compgen -W "\${opts}" -- \${cur}))

    return 0;
}

complete -o default -F _gush gush gush.phar
COMP_WORDBREAKS=\${COMP_WORDBREAKS//:}

EOT;
}
