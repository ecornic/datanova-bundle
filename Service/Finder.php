<?php

namespace Laposte\DatanovaBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class Finder
{
    const DEFAULT_FORMAT = 'JSON';

    /** @var Filesystem $filesystem */
    private $filesystem;

    /** @var string $directory */
    private $directory;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param Filesystem $filesystem
     * @param string $directory
     */
    public function __construct(Filesystem $filesystem, $directory = __DIR__)
    {
        $this->filesystem = $filesystem;
        $this->setWorkingDirectory($directory);
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $directory the working directory for filesystem operations
     */
    public function setWorkingDirectory($directory)
    {
        $exists = $this->filesystem->exists($directory);
        if (!$exists) {
            try {
                $this->filesystem->mkdir($directory);
                $this->logger->notice("Working directory created at " . $directory);
            } catch (IOExceptionInterface $exception) {
                $this->logger->error("An error occurred while creating your directory at " . $exception->getPath(), $exception->getTrace());
            }
        }
        $this->directory = $directory;
    }

    /**
     * Check if a dataset local file exists
     *
     * @param string $dataset
     * @param string $format
     *
     * @return bool
     */
    public function exists($dataset, $format)
    {
        $uri = $this->getFilePath($dataset, $format);

        return $this->filesystem->exists($uri);
    }

    /**
     * @param string $dataset
     * @param string $format
     *
     * @return string
     */
    private function getFilePath($dataset, $format)
    {
        return sprintf('%s%s%s.%s', $this->directory, DIRECTORY_SEPARATOR, $dataset, $format);
    }

    /**
     * Save a records to dataset file
     *
     * @param string $dataset
     * @param string $content
     * @param string $format
     * @param bool $force
     *
     * @return false|string saved file path
     */
    public function save($dataset, $content, $format = self::DEFAULT_FORMAT, $force = false)
    {
        $saved = false;
        $path = $this->getFilePath($dataset, $format);
        if ($this->filesystem->exists($path) && !$force) {
            $this->logger->error("An error occurred while saving existing dataset at " . $path);
        } else {
            try {
                $this->filesystem->dumpFile($path, $content);
                $this->logger->notice(sprintf('Saving %s dataset at %s', $dataset, $path));
                $saved = $path;
            } catch (IOExceptionInterface $exception) {
                $this->logger->error("An error occurred while saving the dataset at " . $exception->getPath(), $exception->getTrace());
            }
        }

        return $saved;
    }
}