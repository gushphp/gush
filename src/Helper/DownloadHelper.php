<?php

namespace Gush\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Progress\Progress;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The DownloadHelper helps with downloading files.
 *
 * Some parts were borrowed from the symfony-installer.
 */
class DownloadHelper extends Helper implements OutputAwareInterface
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var FilesystemHelper
     */
    private $filesystemHelper;

    /**
     * Constructor.
     *
     * @param string|null $directory
     */
    public function __construct(FilesystemHelper $filesystemHelper)
    {
        $this->fs = new Filesystem();
        $this->filesystemHelper = $filesystemHelper;
    }

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'download';
    }

    /**
     * Download a file from the URL to the destination.
     *
     * @param string $url      Fully qualified URL to the file.
     * @param bool   $progress Show the progressbar when downloading.
     */
    public function downloadFile($url, $progress = true)
    {
        /** @var ProgressBar|null $progressBar */
        $progressBar = null;
        $downloadCallback = function ($size, $downloaded, $client, $request, Response $response) use (&$progressBar) {
            // Don't initialize the progress bar for redirects as the size is much smaller.
            if ($response->getStatusCode() >= 300) {
                return;
            }

            if (null === $progressBar) {
                ProgressBar::setPlaceholderFormatterDefinition('max', function (ProgressBar $bar) {
                    return $this->formatSize($bar->getMaxSteps());
                });

                ProgressBar::setPlaceholderFormatterDefinition('current', function (ProgressBar $bar) {
                    return str_pad($this->formatSize($bar->getProgress()), 11, ' ', STR_PAD_LEFT);
                });

                $progressBar = new ProgressBar($this->output, $size);
                $progressBar->setFormat('%current%/%max% %bar%  %percent:3s%%');
                $progressBar->setRedrawFrequency(max(1, floor($size / 1000)));
                $progressBar->setBarWidth(60);

                if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
                    $progressBar->setEmptyBarCharacter('░'); // light shade character \u2591
                    $progressBar->setProgressCharacter('');
                    $progressBar->setBarCharacter('▓'); // dark shade character \u2593
                }

                $progressBar->start();
            }

            $progressBar->setProgress($downloaded);
        };

        $client = $this->getGuzzleClient();

        if ($progress) {
            $this->output->writeln(sprintf("\n Downloading %s...\n", $url));

            $client->getEmitter()->attach(new Progress(null, $downloadCallback));
        }

        $response = $client->get($url);

        $tmpFile = $this->filesystemHelper->newTempFilename();
        $this->fs->dumpFile($tmpFile, $response->getBody());

        if (null !== $progressBar) {
            $progressBar->finish();
            $this->output->writeln("\n");
        }

        return $tmpFile;
    }

    /**
     * Returns the Guzzle client configured according to the system environment
     * (e.g. it takes into account whether it should use a proxy server or not).
     *
     * @return Client
     */
    protected function getGuzzleClient()
    {
        $options = [];

        // Check if the client must use a proxy server.
        if (!empty($_SERVER['HTTP_PROXY']) || !empty($_SERVER['http_proxy'])) {
            $proxy = !empty($_SERVER['http_proxy']) ? $_SERVER['http_proxy'] : $_SERVER['HTTP_PROXY'];
            $options['proxy'] = $proxy;
        }

        return new Client($options);
    }

    /**
     * Utility method to show the number of bytes in a readable format.
     *
     * @param int $bytes The number of bytes to format.
     *
     * @return string The human readable string of bytes (e.g. 4.32MB).
     */
    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = $bytes ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return number_format($bytes, 2).' '.$units[$pow];
    }
}
