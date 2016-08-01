<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Exception\UserException;
use Gush\Feature\GitRepoFeature;
use Gush\Template\Pats\Pats;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestPatOnTheBackCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:pat-on-the-back')
            ->setDescription('Gives a pat on the back to a PR\'s author')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull request number')
            ->addOption('pat', 'p', InputOption::VALUE_REQUIRED, 'A pat name')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command gives a pat on the back to a PR's author:

    <info>$ gush %command.name% 12</info>

If you know which pat you want to use, you can pass it with the <comment>--pat</comment> option:

    <info>$ gush %command.name% 12 --pat=thank_you</info>

Note: You can configure your own pat templates in your local <comment>.gush.yml</comment> file like:
<comment>
pats:
    you_are_great: 'You are great @{{ author }}.'
    nice_catch: 'Very nice catch, thanks @{{ author }}.'
</comment>

You can let gush choose a random pat using <comment>random</comment> pat option:

    <info>$ gush %command.name% 12 --pat=random</info>

<comment>The command will terminate in a failure if the pull request is authored by yourself!</comment>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $prNumber = $input->getArgument('pr_number');

        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);

        if ($pr['user'] === $this->getParameter($input, 'authentication')['username']) {
            throw new UserException('You cannot pat yourself.');
        }

        $config = $this->getConfig();

        if ($customPats = $config->get('pats')) {
            Pats::addPats($customPats);
        }

        $pats = Pats::getPats();

        if ($pat = $input->getOption('pat')) {
            if ('random' === $pat) {
                $pat = Pats::getRandomPatName();
            }
        } else {
            $pat = $this->choosePat($pats);
        }

        $patMessage = $this
            ->getHelper('template')
            ->bindAndRender(['pat' => $pat, 'author' => $pr['user']], 'pats', 'general')
        ;

        $adapter->createComment($prNumber, $patMessage);

        $url = $adapter->getPullRequest($prNumber)['url'];
        $this->getHelper('gush_style')->success("Pat on the back pushed to {$url}");

        return self::COMMAND_SUCCESS;
    }

    private function choosePat(array $pats)
    {
        return $this->getHelper('gush_style')->choice('Please, choose a pat ', $pats, reset($pats));
    }
}
