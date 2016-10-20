<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Branch;

use Gush\Command\BaseCommand;
use Gush\Feature\GitDirectoryFeature;
use Gush\Feature\IssueTrackerRepoFeature;
use Gush\Helper\TemplateRenderHelper;
use Gush\Util\StringUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BranchChangelogCommand extends BaseCommand implements IssueTrackerRepoFeature, GitDirectoryFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:changelog')
            ->setDescription('Reports what got fixed or closed since last release on the given branch')
            ->addArgument(
                'branch',
                InputArgument::OPTIONAL,
                'Branch to look for tags in. When unspecified, the current branch is used'
            )
            ->addOption(
                'search',
                's',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Regex pattern to use for searching',
                ['/#(?P<id>[0-9]+)/i']
            )
            ->setHelp(
                <<<EOF
Reports what got fixed or closed since the last release on the given branch.

    <info>$ gush %command.name%</info>

This command will search all the commits in the given branch (that were made after the last tag)
and will try to extract the issue numbers from the message. To only match a precise pattern, use the
<comment>--search</comment> option to specify one or multiple regex-patterns (with delimiters and flags).

For example, if your issues are prefixed with "DC-", use the following:

    <info>$ gush %command.name% --search="{DC-(?P<id>[0-9]+)}i"</info>

Note: It's important the regex has a "named capturing group" identified by "id" like <comment>(?P<id>[0-9]+)</comment>.
This named group must (only) match the issue number and nothing else.

To learn more about composing your own regex patterns see:
http://php.net/manual/reference.pcre.pattern.syntax.php
http://www.regular-expressions.info/

Changelog format
----------------

The default changelog format is <comment>title ([#id](url))</comment>
which is perfectly usable for markdown. But you can easily change this 
to any format you want, in fact you can even the entire template.

Add the following to your local <comment>.gush.yml</comment> file:

<comment>
templates:
    changelog: |
        {% for item in items %}
        * {{ item.title }} ([{{ item.id }}]({{ item.url }})) by {{ item.user }}
        {% endfor %}
</comment>

The <comment>templates.changelog</comment> expects a Twig template as string.

The <comment>items</comment> array contains array of all the resolved issues.
Each item contains the following keys:

<comment>
* url
* number (id of the issue/pull-request)
* state (open, closed)
* title
* body
* user (opener of the issue/pull-request)
* labels (array)
* assignee
* milestone
* created_at
* updated_at
* closed_by
* pull_request (bool, whether this is a pull-request)
* id (id as found in the commit message)
* commit (the commit-hash)
</comment>

Learn more about the Twig template syntax: http://twig.sensiolabs.org/
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branch = $input->getArgument('branch') ?: $this->getHelper('git')->getActiveBranchName();

        try {
            $latestTag = $this->getHelper('git')->getLastTagOnBranch($branch);
        } catch (\RuntimeException $e) {
            $this->getHelper('gush_style')->note(
                sprintf('No tags were found on branch "%s".', $branch)
            );

            return self::COMMAND_SUCCESS;
        }

        $adapter = $this->getIssueTracker();
        $commits = $this->getHelper('git')->getLogBetweenCommits($latestTag, $branch);
        $issues = $this->getIssuesFromCommits($commits, $input->getOption('search'));

        $output->writeln(sprintf('<info>Found %s commits</info>', count($commits)), OutputInterface::VERBOSITY_DEBUG);

        $items = [];
        foreach ($issues as $id => list($commit, $idLabel)) {
            // ignore missing issues
            try {
                $issue = $adapter->getIssue($id);
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()), OutputInterface::VERBOSITY_DEBUG);
                continue;
            }

            $items[] = array_merge($issue, ['commit' => $commit, 'id' => $idLabel]);
        }

        /** @var TemplateRenderHelper $templating */
        $templating = $this->getHelper('template_render');

        $output->writeln(
            StringUtil::splitLines(
                $templating->renderTemplate(
                    $templating->findTemplate('changelog', 'changelog.twig'),
                    ['items' => $items]
                )
            )
        );

        return self::COMMAND_SUCCESS;
    }

    private function getIssuesFromCommits(array $commits, array $searchPatterns)
    {
        $issues = [];

        foreach ($commits as $commit) {
            foreach ($searchPatterns as $regex) {
                if (preg_match($regex, $commit['message'], $matchesGush) && isset($matchesGush['id'])) {
                    $issues[$matchesGush['id']] = [$commit['sha'], $matchesGush[0]];
                }
            }
        }

        ksort($issues);

        return $issues;
    }
}
