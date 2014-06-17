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

use Symfony\Component\Console\Helper\Helper;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class AutocompleteHelper extends Helper
{
    /**
     * @var string
     */
    protected $autocompleteScript = <<<EOL
#!/bin/sh
_gush()
{
    local cur prev coms opts
    COMPREPLY=()
    cur="\${COMP_WORDS[COMP_CWORD]}"
    prev="\${COMP_WORDS[COMP_CWORD-1]}"
    coms="%%COMMANDS%%"
    opts="%%SHARED_OPTIONS%%"

    if [[ \${COMP_CWORD} = 1 ]] ; then
        COMPREPLY=($(compgen -W "\${coms}" -- \${cur}))

        return 0
    fi

    case "\${prev}" in
        %%SWITCH_CONTENT%%
        esac

    COMPREPLY=($(compgen -W "\${opts}" -- \${cur}))

    return 0;
}

complete -o default -F _gush gush
COMP_WORDBREAKS=\${COMP_WORDBREAKS//:}

EOL;

    /**
     * @param array $commands
     *
     * @return string
     */
    public function getAutoCompleteScript(array $commands)
    {
        $dump = [];

        foreach ($commands as $command) {
            $options = [];
            foreach ($command['definition']['options'] as $option) {
                $options[] = (string) $option['name'];
            }

            $dump[$command['name']] = $options;
        }

        $commonOptions = [];
        foreach ($dump as $command => $options) {
            if (empty($commonOptions)) {
                $commonOptions = $options;
            }

            $commonOptions = array_intersect($commonOptions, $options);
        }

        $dump = array_map(function ($options) use ($commonOptions) {
                return array_diff($options, $commonOptions);
            }, $dump);

        $switchCase = <<<SWITCHCASE
    %%COMMAND%%)
            opts="\${opts} %%COMMAND_OPTIONS%%"
            ;;
SWITCHCASE;

        $switchContent = '';
        foreach ($dump as $command => $options) {
            if (empty($options)) {
                continue;
            }

            $switchContent .= str_replace(
                ['%%COMMAND%%', '%%COMMAND_OPTIONS%%'],
                [$command, join(' ', $options)],
                $switchCase
            );
        }

        return str_replace(
            ['%%COMMANDS%%', '%%SHARED_OPTIONS%%', '%%SWITCH_CONTENT%%'],
            [implode(' ', array_column($commands, 'name')), implode(' ', $commonOptions), $switchContent],
            $this->autocompleteScript
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'autocomplete';
    }
}
