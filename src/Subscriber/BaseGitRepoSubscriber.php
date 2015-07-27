<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Subscriber;

use Gush\Adapter\Adapter;
use Gush\Application;
use Gush\Config;
use Gush\Exception\UserException;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Gush\Helper\StyleHelper;
use Gush\Util\ConfigUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class BaseGitRepoSubscriber implements EventSubscriberInterface
{
    protected $application;
    protected $gitHelper;
    protected $gitConfigHelper;
    protected $styleHelper;

    public function __construct(
        Application $application,
        GitHelper $gitHelper,
        GitConfigHelper $gitConfigHelper,
        StyleHelper $styleHelper
    ) {
        $this->application = $application;
        $this->gitHelper = $gitHelper;
        $this->gitConfigHelper = $gitConfigHelper;
        $this->styleHelper = $styleHelper;
    }

    protected function getSupportedAdapters($type)
    {
        return implode(', ', array_keys($this->application->getAdapterFactory()->allOfType($type)));
    }

    /**
     * @param string $adapterName
     *
     * @return \Gush\Adapter\BaseAdapter
     *
     * @throws UserException
     */
    protected function getAdapter($adapterName, $config)
    {
        $hash = ConfigUtil::generateConfigurationIdentifier($adapterName, $config);

        $config = $this->application->getConfig()->get(['adapters', $hash], Config::CONFIG_SYSTEM);
        $adapter = $this->application->getAdapterFactory()->createRepositoryManager(
            $adapterName,
            $config,
            $this->application->getConfig()
        );

        $adapter->authenticate();

        return $adapter;
    }

    /**
     * @param Adapter     $adapter
     * @param string|null $org
     * @param string|null $repo
     *
     * @return array [org, repo]
     *
     * @throws UserException
     */
    protected function getRepositoryReference(Adapter $adapter, $org, $repo)
    {
        $remote = $this->findRemoteName(false);
        $repoInfo = $this->gitConfigHelper->getRemoteInfo($remote);

        if (null === $org) {
            $org = $repoInfo['vendor'];
        }

        if (null === $repo) {
            $repo = $repoInfo['repo'];
        }

        $adapterRepoInfo = $adapter->getRepositoryInfo($org, $repo);

        if ($adapterRepoInfo['is_fork']) {
            $org = $adapterRepoInfo['fork_origin']['org'];
            $repo = $adapterRepoInfo['fork_origin']['repo'];
        }

        return [$org, $repo];
    }

    /**
     * @param bool $allowFailure
     *
     * @return null|string
     *
     * @throws UserException
     */
    protected function findRemoteName($allowFailure = true)
    {
        $config = $this->application->getConfig();
        $adapter = $config->get('repo_adapter', Config::CONFIG_LOCAL);
        $username = null;

        if (null !== $adapter) {
            $username = $config->getFirstNotNull(
                [
                    ['adapters', $adapter, 'username'],
                    ['adapters', $adapter, 'authentication', 'username'],
                ],
                Config::CONFIG_SYSTEM
            );
        }

        if (null !== $username && $this->gitConfigHelper->remoteExists($username)) {
            return $username;
        }

        if ($this->gitConfigHelper->remoteExists('origin')) {
            return 'origin';
        }

        if (!$allowFailure) {
            throw new UserException(
                sprintf(
                    'Unable to get the repository information, Git remote "%s" should be set for automatic detection.',
                    implode('" or "', array_filter(['origin', $username], 'strlen'))
                )
            );
        }
    }
}
