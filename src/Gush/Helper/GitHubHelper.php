<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;

class GitHubHelper extends Helper
{
    public static $enum = [
        'issue' => [
            'filter' => [
                'desc' => 'Filter to apply to issues',
                'values' => [
                    'assigned',
                    'created',
                    'mentioned',
                    'subscribed',
                    'all',
                ],
            ],
            'state' => [
                'desc' => 'Issue state',
                'values' => [
                    'open',
                    'closed',
                ],
            ],
            'sort' => [
                'desc' => 'Sort issues by',
                'values' => [
                    'created',
                    'updated',
                ],
            ],
            'direction' => [
                'desc' => 'Sort direction',
                'values' => [
                    'asc', 'desc',
                ],
            ],
            'type' => [
                'desc' => 'Issue type',
                'values' => [
                    'pr', 'issue',
                ],
            ],
        ],
    ];

    public function getName()
    {
        return 'github';
    }

    /**
     * Validate the given enum fields in the given Input
     *
     * @param $input InputInterface  - Input which contains the options to be validated
     * @param string $domain - Domain of the enum e.g. issue
     * @param array  $types  - Types to validate (e.g. filter ,state, etc)
     *
     * @throws \InvalidArgumentException
     *
     * @return array Array of key value pairs
     */
    public static function validateEnums(InputInterface $input, $domain, $types = [])
    {
        $ret = [];

        foreach ($types as $type) {
            $v = $input->getOption($type);
            if (null !== $v) {
                self::validateEnum($domain, $type, $v);
                $ret[$type] = $v;
            }
        }

        return $ret;
    }

    private static function validateEnumDomainAndType($domain, $type)
    {
        if (!isset(self::$enum[$domain])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown enum domain "%s"', $domain)
            );
        }

        if (!isset(self::$enum[$domain][$type])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown enum type "%s" in domain "%s', $domain, $type)
            );
        }
    }

    /**
     * Check if a given value is a valid enum
     *
     * @param string $domain - Domain of the enum e.g. issue
     * @param string $type   - Type to validate (e.g. filter ,state, etc)
     * @param mixed  $value
     *
     * @throws \InvalidArgumentException
     */
    public static function validateEnum($domain, $type, $value)
    {
        self::validateEnumDomainAndType($domain, $type);

        if (!in_array($value, self::$enum[$domain][$type]['values'])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown value "%s" for "%s"', $value, $type)
            );
        };
    }

    /**
     * Return a description for the given enum type.
     *
     * @param string $domain - Domain of the enum e.g. issue
     * @param string $type   - Type to validate (e.g. filter ,state, etc)
     *
     * @return string
     */
    public static function formatEnum($domain, $type)
    {
        self::validateEnumDomainAndType($domain, $type);

        return sprintf(
            '%s (One of <comment>%s</comment>)',
            self::$enum[$domain][$type]['desc'],
            implode('</comment>, <comment>', self::$enum[$domain][$type]['values'])
        );
    }
}
