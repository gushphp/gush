<?php

namespace Gush\Helper;

use Gush\Exception\UnsupportedTypeException;
use Symfony\Component\Console\Helper\Helper;
use Gush\Meta;

class MetaHelper extends Helper
{
    /**
     * @var array
     */
    protected $supportedFiles = [];

    public function __construct()
    {
        $this->supportedFiles = [
            'php'  => new Meta\Base,
            'js'   => new Meta\Base,
            'css'  => new Meta\Base,
            'twig' => new Meta\Twig,
        ];
    }

    /**
     * @param string             $fileType
     * @param Meta\MetaInterface $class
     */
    public function registerFileType($fileType, Meta\MetaInterface $class)
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
     * @throws \Gush\Exception\UnsupportedTypeException
     * @return string
     */
    public function renderHeader($header, $type)
    {
        $class = $this->getMetaClass($type);

        $out = [$class->getStartDelimiter()];
        foreach (explode("\n", $header) as $line) {
            // avoid trailing spaces
            $out[] = ' ' . $class->getDelimiter() . ($line ? ' ' . $line : '');
        }
        $out[] = ' ' . $class->getEndDelimiter();
        $out[] = "\n";

        return implode("\n", $out);
    }

    /**
     * @param string $type
     * @return Meta\MetaInterface
     * @throws \Gush\Exception\UnsupportedTypeException
     */
    public function getMetaClass($type)
    {
        if (!isset($this->supportedFiles[$type])) {
            throw new UnsupportedTypeException($type, array_keys($this->supportedFiles));
        }

        return $this->supportedFiles[$type];
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'meta';
    }
}