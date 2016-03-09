<?php

namespace Laposte\DatanovaBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class Finder
{
    const DEFAULT_FORMAT = 'JSON';
    const RESSOURCES_FOLDER = '@LaposteDatanovaBundle/Resources/dataset';

    /** @var Filesystem $filesystem */
    private $filesystem;

    /** @var FileLocator $locator */
    private $locator;

    /** @var string $directory */
    private $directory;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param Filesystem $filesystem
     * @param FileLocator $locator
     * @param string $rootDir
     */
    public function __construct(Filesystem $filesystem, FileLocator $locator, $rootDir = self::RESSOURCES_FOLDER)
    {
        $this->filesystem = $filesystem;
        $this->locator = $locator;
        $rootDir = $this->locator->locate($rootDir);
        $this->setWorkingDirectory($rootDir);
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
        $directory = preg_replace('#/+#', '/', $directory); // remove multiple slashes
        try {
            $directory = $this->locator->locate($directory);
        } catch (\Exception $exception) {
        }
        $exists = $this->filesystem->exists($directory);
        if (!$exists) {
            try {
                $this->filesystem->mkdir($directory);
                $this->logger->notice("Working directory created at " . $directory);
            } catch (IOExceptionInterface $exception) {
                $this->logger->error(
                    "An error occurred while creating your directory at " . $exception->getPath(),
                    $exception->getTrace()
                );
            }
        }
        $this->directory = $directory;
    }

    /**
     * Check if a dataset local file exists
     *
     * @param string $dataset
     * @param string $format
     * @param string $filter
     *
     * @return bool
     */
    public function exists($dataset, $format, $filter = null)
    {
        $uri = $this->getFilePath($dataset, $format, $filter);

        return $this->filesystem->exists($uri);
    }

    /**
     * @param string $dataset
     * @param string $format
     * @param string $filter
     *
     * @return string
     */
    private function getFilePath($dataset, $format, $filter = null)
    {
        $filter = preg_replace('#:|=#', '_', $filter);
        $filepath = sprintf(
            '%s%s%s%s%s_%s.%s',
            $this->directory,
            DIRECTORY_SEPARATOR,
            $dataset,
            DIRECTORY_SEPARATOR,
            $dataset,
            $filter,
            $format
        );
        $filepath = preg_replace('#/+#', '/', $filepath); // remove multiple slashes

        return $filepath;
    }

    /**
     * Save a records to dataset file
     *
     * @param string $dataset
     * @param string $content
     * @param string $format
     * @param string $filter
     * @param bool $force
     *
     * @return false|string saved file path
     */
    public function save($dataset, $content, $format = self::DEFAULT_FORMAT, $filter = null, $force = false)
    {
        $saved = false;
        $filename = $dataset;
        $path = $this->getFilePath($filename, $format, $filter);
        if ($this->filesystem->exists($path) && !$force) {
            $this->logger->error("An error occurred while saving existing dataset at " . $path);
        } else {
            try {
                $this->filesystem->dumpFile($path, $content);
                $this->logger->notice(sprintf('Saving %s dataset at %s', $dataset, $path));
                $saved = realpath($path);
            } catch (IOExceptionInterface $exception) {
                $this->logger->error(
                    "An error occurred while saving the dataset at " . $exception->getPath(),
                    $exception->getTrace()
                );
            }
        }

        return $saved;
    }

    /**
     * @param string $dataset
     * @param string $format
     * @param string $filter
     *
     * @return false|string dataset file path
     */
    public function findDataset($dataset, $format = self::DEFAULT_FORMAT, $filter = null)
    {
        $datasetPath = false;
        $path = $this->getFilePath($dataset, $format, $filter);
        if ($this->filesystem->exists($path)) {
            $datasetPath = realpath($path);
        }

        return $datasetPath;
    }

    /**
     * @param string $filepath
     *
     * @return null|string
     */
    public function getContent($filepath)
    {
        $content = null;
        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
        }

        return $content;
    }
}
