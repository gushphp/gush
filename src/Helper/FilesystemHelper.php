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

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Filesystem\Filesystem as SfFilesystem;

class FilesystemHelper extends Helper
{
    /**
     * @var string
     */
    private $tempFilenames = [];

    /**
     * @var SfFilesystem
     */
    private $fs;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->fs = new SfFilesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'filesystem';
    }

    public function newTempFilename()
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gush';
        $this->fs->mkdir($dir);

        $tmpName = tempnam($dir, '');
        $this->tempFilenames[] = $tmpName;

        return $tmpName;
    }

    /**
     * Remove all the temp-file that were created
     * with newTempFilename().
     */
    public function clearTempFiles()
    {
        $this->fs->remove($this->tempFilenames);
    }
}
