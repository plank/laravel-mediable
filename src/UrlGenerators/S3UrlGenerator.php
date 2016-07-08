<?php

namespace Plank\Mediable\UrlGenerators;

use Plank\Mediable\Exceptions\MediaUrlException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\FilesystemManager;

/**
 * S3 Url Generator
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class S3UrlGenerator extends BaseUrlGenerator
{

    /**
     * Filesystem Manager
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * Constructor
     * @param Config            $config
     * @param FilesystemManager $filesystem
     */
    public function __construct(Config $config, FilesystemManager $filesystem)
    {
        parent::__construct($config);
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritDoc}
     */
    public function getAbsolutePath()
    {
        return $this->getUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function isPubliclyAccessible()
    {
        //file permissions are set on the buckets themselves
        //use config value if possible
        return $this->getDiskConfig('visibility', 'public') == 'public';
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        if (!$this->isPubliclyAccessible()) {
            throw MediaUrlException::cloudMediaNotPubliclyAccessible($this->media->disk);
        }
        return $this->filesystem->disk($this->media->disk)->url($this->media->getDiskPath());
    }
}
