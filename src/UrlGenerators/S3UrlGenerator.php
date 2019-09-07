<?php
declare(strict_types=1);

namespace Plank\Mediable\UrlGenerators;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\FilesystemManager;
use Plank\Mediable\Exceptions\MediaUrlException;

class S3UrlGenerator extends BaseUrlGenerator
{
    /**
     * Filesystem Manager.
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * Constructor.
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Filesystem\FilesystemManager $filesystem
     */
    public function __construct(Config $config, FilesystemManager $filesystem)
    {
        parent::__construct($config);
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getAbsolutePath(): string
    {
        return $this->getUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): string
    {
        if (!$this->isPubliclyAccessible()) {
            throw MediaUrlException::cloudMediaNotPubliclyAccessible($this->media->disk);
        }

        $adapter = $this->filesystem->disk($this->media->disk)->getDriver()->getAdapter();
        return $adapter->getClient()->getObjectUrl(
            $adapter->getBucket(), $this->media->getDiskPath()
        );
    }
}
