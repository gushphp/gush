<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Bitbucket\API\Http\ClientInterface;
use Buzz\Message\Request;
use Gush\Adapter\Listener\ResultPagerListener;

/**
 * Pager class for supporting pagination in BitBucket.
 */
class ResultPager
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var array pagination
     */
    protected $pagination;

    /**
     * @var int
     */
    protected $perPage;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var ResultPagerListener
     */
    protected $pagerListener;

    /**
     * @param BitBucketClient     $client
     * @param ResultPagerListener $pagerListener
     */
    public function __construct(BitBucketClient $client, ResultPagerListener $pagerListener)
    {
        $this->client = $client->getHttpClient();
        $this->pagerListener = $pagerListener;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->pagerListener->setPage($page);
        $this->page = $page;
    }

    /**
     * @param int|null $perPage
     */
    public function setPerPage($perPage)
    {
        $this->pagerListener->setPerPage($perPage);
        $this->perPage = $perPage;
    }

    /**
     * Fetches all the results.
     *
     * @param array  $result
     * @param string $valuesKey
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    public function fetch($result, $valuesKey = 'values')
    {
        if ('1.0' === $this->client->getApiVersion()) {
            return $this->fetchApi1($result, $valuesKey);
        }

        /** @var Request $request */
        $request = $this->client->getLastRequest();

        if (!array_key_exists('values', $result)) {
            throw new \RuntimeException(
                sprintf(
                    'No values-key "values" found in resource "%s", please report this bug to the Gush developers.',
                    $request->getResource()
                )
            );
        }

        $fullResult = $result['values'];

        // BitBucket please fix your API..
        // https://bitbucket.org/site/master/issue/9659/pagelen-sometimes-limited-to-100
        // Luckily we only need one extra request
        if (null !== $this->perPage && $this->perPage > 50 && isset($result['next']) && false !== strpos($request->getResource(), '/pullrequests')) {
            $response = $this->client->get($result['next']);
            $result = json_decode($response->getContent(), true);
            $fullResult = array_merge($fullResult, $result['values']);
        }

        // Adapter maximum is 100, which is also the maximum of BitBucket's API
        if (null === $this->perPage) {
            while (isset($result['next'])) {
                $response = $this->client->get($result['next']);
                $result = json_decode($response->getContent(), true);
                $fullResult = array_merge($fullResult, $result['values']);
            }
        }

        return $fullResult;
    }

    /**
     * Fetches all the results, using API version 1.0
     *
     * @param array  $result
     * @param string $valuesKey
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function fetchApi1($result, $valuesKey = 'values')
    {
        /** @var Request $request */
        $request = $this->client->getLastRequest();

        if (!array_key_exists($valuesKey, $result)) {
            throw new \RuntimeException(
                sprintf(
                    'No values-key "%s" found in resource "%s", please report this bug to the Gush developers.',
                    $valuesKey,
                    $request->getResource()
                )
            );
        }

        $fullResult = $result[$valuesKey];
        if ($this->perPage !== null && $this->perPage <= 50) {
            return $fullResult;
        }

        // The api is limited to 50 per page
        // So everything higher then 50 requires additional call(s)

        $urlComponents = parse_url($request->getResource());
        parse_str($urlComponents['query'], $query);
        $urlComponents['query'] = $query;

        $url = $request->getHost().$urlComponents['path'].'?';
        $limit = $urlComponents['query']['limit'];
        $count = isset($result['count']) ? $result['count'] : $urlComponents['limit'];
        $pages = ceil($count / $limit);

        for ($page = $this->page + 1; $page <= $pages; $page++) {
            $urlComponents['query']['start'] = abs($page - 1) * $limit;

            $response = $this->client->get($url.http_build_query($urlComponents['query'], '', '&'));
            $result = json_decode($response->getContent(), true);

            $fullResult = array_merge($fullResult, $result[$valuesKey]);

            // We only wanted one, so stop now
            if ($this->perPage !== null) {
                break;
            }
        }

        return $fullResult;
    }
}
