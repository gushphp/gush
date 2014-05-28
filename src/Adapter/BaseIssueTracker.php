<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Gush\Config;
use Gush\Exception\AdapterException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adapter is the interface implemented by all Gush Adapter classes.
 *
 * Note that each adapter instance can be only used for one repository.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class BaseIssueTracker implements IssueTracker
{
	/**
	 * @var Config
	 */
	protected $configuration;

	/**
	 * @var null|string
	 */
	protected $username;

	/**
	 * @var null|string
	 */
	protected $repository;

	/**
	 * @param string $username
	 *
	 * @return $this
	 */
	public function setUsername($username)
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUsername()
	{
		return $this->username;
	}

	public function setRepository($repository)
	{
		$this->repository = $repository;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRepository()
	{
		return $this->repository;
	}
}
