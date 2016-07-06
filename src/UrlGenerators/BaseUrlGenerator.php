<?php

namespace Frasmage\Mediable\UrlGenerators;

use Frasmage\Mediable\Media;
use Illuminate\Contracts\Config\Repository as Config;

abstract class BaseUrlGenerator implements UrlGenerator{
    protected $config;
    protected $media;

    public function __construct(Config $config){
        $this->config = $config;
    }

    public function setMedia(Media $media){
        $this->media = $media;
    }

    abstract public function isPubliclyAccessible();

    abstract public function getUrl();

    protected function getDiskConfig($key, $default = null){
        return $this->config->get("filesystems.disks.{$this->media->disk}.{$key}", $default);
    }
}
