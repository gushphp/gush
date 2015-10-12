<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter\Listener;

use Bitbucket\API\Http\Client;
use Bitbucket\API\Http\Listener\ListenerInterface;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;

class ResultPagerListener implements ListenerInterface
{
    /**
     * @var integer
     */
    private $perPage = null;

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param int $perPage
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = $page < 0 ? 1 : $page;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    public function getName()
    {
        return 'result_pager';
    }

    public function preSend(RequestInterface $request)
    {
        if ($request::METHOD_GET !== $request->getMethod() || null === $this->page) {
            return;
        }

        $resource = $request->getResource();

        // Already configured, properly a secondary request
        if (false !== strpos($resource, 'pagelen=') xor false !== strpos($resource, 'limit=')) {
            return;
        }

        $urlComponents = parse_url($resource);
        if (!isset($urlComponents['query'])) {
            $urlComponents['query'] = [];
        } else {
            parse_str($urlComponents['query'], $query);
            $urlComponents['query'] = $query;
        }

        /*
         * Page limiting works as follow (only when perPage is higher then maximum):
         *
         * Divide perPage by two, so each page covers exactly two request.
         * We have a maximum of 100 (unless the limit is disabled) so no crazy math or truncating is required.
         */

        if ('2.0' === $this->client->getApiVersion()) {
            $urlComponents['query']['pagelen'] = null === $this->perPage ? 100 : $this->perPage;
            $urlComponents['query']['page'] = $this->page;

            // BitBucket please fix your API..
            // https://bitbucket.org/site/master/issue/9659/pagelen-sometimes-limited-to-100
            if (false !== strpos($urlComponents['path'], '/pullrequests') && $urlComponents['query']['pagelen'] > 50) {
                $urlComponents['query']['pagelen'] = $urlComponents['query']['pagelen'] / 2;
            }
        } elseif ('1.0' === $this->client->getApiVersion()) {
            if (null === $this->perPage) {
                // Use the maximum
                $limit = 50;
            } elseif ($this->perPage > 50) {
               // Divide perPage by two, so each page covers exactly two request (no crazy math or truncating).
               $limit = $this->perPage / 2;
            } else {
               $limit = $this->perPage;
            }

            $urlComponents['query']['limit'] = $limit;
            $urlComponents['query']['start'] = abs($this->page - 1) * $limit;
        }

        $request->setResource($urlComponents['path'].'?'.http_build_query($urlComponents['query'], '', '&'));
    }

    public function postSend(RequestInterface $request, MessageInterface $response)
    {
        // noop
    }
}
