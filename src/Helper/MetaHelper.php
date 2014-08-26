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
use Gush\Meta\Iterator\PathFilterIterator;
use Gush\Meta\Meta;
use Symfony\Component\Console\Helper\Helper;

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
     * @param string $fileType
     * @param Meta   $class
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
        foreach (preg_split('{\r?\n}', $header) as $line) {
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
     * @param Meta   $meta
     * @param string $fileContent
     *
     * @return bool
     */
    public function isUpdatable(Meta $meta, $fileContent)
    {
        if (null !== $meta->getStartTokenRegex()) {
            // When no start token is found just ignore the content to prevent corrupting
            if (!preg_match($meta->getStartTokenRegex(), $fileContent, $startMatch)) {
                return false;
            }

            $fileContent = substr($fileContent, strlen($startMatch[0]));
        }

        // Check for preservation header
        if (preg_match('&^'.preg_quote($meta->getStartDelimiter()).'?!&', $fileContent)) {
            return false;
        }

        return true;
    }

    /**
     * Filter the list of files to update.
     *
     * @param array $filesList
     * @param array $excludesList
     *
     * @return array
     */
    public function filterFilesList(array $filesList, array $excludesList)
    {
        return iterator_to_array(new PathFilterIterator(new \ArrayIterator($filesList), [], $excludesList));
    }

    /**
     * Update the content with the header.
     *
     * Note. We only support comments in the beginning of the file.
     * If there is already a comment its replaced, if its missing its added.
     *
     * @param Meta   $meta
     * @param string $header
     * @param string $fileContent
     *
     * @return string
     */
    public function updateContent(Meta $meta, $header, $fileContent)
    {
        if (!$this->isUpdatable($meta, $fileContent)) {
            return $fileContent;
        }

        $startContent = '';
        $fileContent = ltrim($fileContent);

        if (null !== $meta->getStartTokenRegex()) {
            // When no start token is found just ignore the content to prevent corrupting
            if (!preg_match($meta->getStartTokenRegex(), $fileContent, $startMatch)) {
                return $fileContent;
            }

            $startToken = $startMatch[0];
            $startContent = trim($startToken)."\n\n";
            $fileContent = substr($fileContent, strlen($startMatch[0]));
        }

        if (preg_match('&^'.preg_quote($meta->getStartDelimiter()).'?&', $fileContent)) {
            $lines = preg_split("/\r\n|\n|\r/", $fileContent);

            $lineNum = 0;
            $linesCount = count($lines);
            $line = $lines[$lineNum];

            // Skip the comment lines till the end delimiter
            while ($lineNum < $linesCount && (!preg_match('&^\h*'.preg_quote($meta->getEndDelimiter()).'&', $line))) {
                $line = $lines[$lineNum];
                unset($lines[$lineNum]);

                $lineNum++;
            }

            $fileContent = implode("\n", $lines);
        }

        $newContent = $startContent.$header.ltrim($fileContent);

        return $newContent;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'meta';
    }
}
