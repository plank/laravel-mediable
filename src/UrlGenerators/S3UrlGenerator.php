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
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * Constructor.
     * @param Config            $config
     * @param FilesystemManager $filesystem
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

        return $this->filesystem->disk($this->media->disk)->url($this->media->getDiskPath());
    }
}
