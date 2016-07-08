<?php

namespace Frasmage\Mediable\UrlGenerators;

use Frasmage\Mediable\Exceptions\MediaUrlException;
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
     * @var FilesystemManager
     */
    protected $filesystem;

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
        throw MediaUrlException::cannotGetAbsolutePath($this->media->disk);
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
