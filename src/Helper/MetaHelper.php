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

use Gush\Exception\UnsupportedTypeException;
use Gush\Meta\Meta;
use Symfony\Component\Console\Helper\Helper;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class MetaHelper extends Helper
{
    /**
     * @var array
     */
    protected $supportedFiles;

    public function __construct($supportedExtensionClassCollection)
    {
        $this->supportedFiles = $supportedExtensionClassCollection;
        foreach ($this->supportedFiles as $type => $class) {
            if (!$class instanceof Meta) {
                throw new \Exception(
                    sprintf(
                        'Meta header class for type "%s" does not implement the Gush\Meta\Meta interface.',
                        $type
                    )
                );
            }
        }
    }

    /**
     * @param string    $fileType
     * @param Meta $class
     */
    public function registerFileType($fileType, Meta $class)
    {
        $this->supportedFiles[$fileType] = $class;
    }

    /**
     * @return array
     */
    public function getSupportedFiles()
    {
        return $this->supportedFiles;
    }

    /**
     * @param string $header
     * @param string $type
     *
     * @return string
     */
    public function renderHeader($header, $type)
    {
        $class = $this->getMetaClass($type);

        $out = [$class->getStartDelimiter()];
        foreach (explode("\n", $header) as $line) {
            // avoid trailing spaces
            $out[] = ' '.$class->getDelimiter().($line ? ' '.$line : '');
        }
        $out[] = ' '.$class->getEndDelimiter();
        $out[] = "\n";

        return implode("\n", $out);
    }

    /**
     * @param string $type
     *
     * @return Meta
     * @throws UnsupportedTypeException
     */
    public function getMetaClass($type)
    {
        if (!isset($this->supportedFiles[$type])) {
            throw new UnsupportedTypeException($type, array_keys($this->supportedFiles));
        }

        return $this->supportedFiles[$type];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'meta';
    }
}
