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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Helper\Helper;

/**
 * Helper for launching external editor
 *
 * @author Daniel Leech <daniel@dantleech.com>
 * @author Luis Cordova <cordoval@gmail.com>
 */
class EditorHelper extends Helper
{
    /**
     * Launch an external editor and open a temporary
     * file containing the given string value.
     *
     * @param string $string
     *
     * @throws \RuntimeException
     * @return string
     */
    public function fromString($string)
    {
        $fs = new Filesystem();
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gush';

        if (!file_exists($dir)) {
            $fs->mkdir($dir);
        }

        $tmpName = tempnam($dir, '');
        file_put_contents($tmpName, $string);
        $editor = getenv('EDITOR');

        if (!$editor) {
            throw new \RuntimeException('No EDITOR environment variable set.');
        }

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $processHelper = $this->getHelperSet()->get('process');
            /** @var ProcessHelper $processHelper */
            $process = $processHelper->getProcessBuilder($editor.' '. escapeshellarg($tmpName))->getProcess();
            $process->setTimeout(null);
            $process->start();

            // Wait till editor closes
            $process->wait();
        } else {
            system($editor.' '.$tmpName.' > `tty`');
        }

        $contents = file_get_contents($tmpName);
        $fs->remove($tmpName);

        return $contents;
    }

    public function fromStringWithMessage($string, $message, $messagePrefix = '# ')
    {
        $source = [];
        $sourceString = '';
        if (null !== $message) {
            $message = explode("\n", $message);

            foreach ($message as $line) {
                $source[] = $messagePrefix.$line;
            }
            $sourceString = implode("\n", $source).PHP_EOL;
        }

        $sourceString .= $string;

        $res = $this->fromString($sourceString);
        $res = explode("\n", $res);

        $line = current($res);

        while (0 === strpos($line, $messagePrefix)) {
            $line = next($res);
        }

        $out = [];

        while ($line) {
            $out[] = $line;
            $line = next($res);
        }

        return implode("\n", $out);
    }

    public function getName()
    {
        return 'editor';
    }
}
