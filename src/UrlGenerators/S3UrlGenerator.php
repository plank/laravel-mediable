<?php

namespace Plank\Mediable\UrlGenerators;

use Plank\Mediable\Exceptions\MediaUrlException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\FilesystemManager;

/**
 * S3 Url Generator.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class S3UrlGenerator extends BaseUrlGenerator
{
    /**
     * Filesystem Manager.
     * @var \Illuminate\Filesystem\FilesystemManager
     */
    protected $filesystem;

    /**
     * Constructor.
     * @param Illuminate\Contracts\Config\Repository   $config
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
    public function getAbsolutePath()
    {
        return $this->getUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        if (! $this->isPubliclyAccessible()) {
            throw MediaUrlException::cloudMediaNotPubliclyAccessible($this->media->disk);
        }

        $adapter = $this->filesystem->disk($this->media->disk)->getDriver()->getAdapter();
        return $adapter->getClient()->getObjectUrl($adapter->getBucket(), $this->media->getDiskPath());
    }
}
