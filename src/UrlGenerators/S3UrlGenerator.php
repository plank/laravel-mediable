<?php

namespace Frasmage\Mediable\UrlGenerators;

use Frasmage\Mediable\Exceptions\MediaUrlException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\FilesystemManager;

abstract class S3UrlGenerator extends BaseUrlGenerator{

    protected $filesystem;

    public function __construct(Config $config, FilesystemManager $filesystem){
        parent::__construct($config);
        $this->filesystem = $filesystem;
    }

    public function getAbsolutePath()
    {
        throw MediaUrlException::cannotGetAbsolutePath($this->media->disk);
    }

    public function isPubliclyAccessible()
    {
        //file permissions are set on the buckets themselves
        //use config value if possible
        return $this->getDiskConfig('visibility', 'public') == 'public';
    }

    public function getUrl()
    {
        if (!$this->isPubliclyAccessible()) {
            throw MediaUrlException::cloudMediaNotPubliclyAccessible($this->media->disk);
        }
        return $this->filesystem($this->media->disk)->url($this->media->getDiskPath());
    }
}
