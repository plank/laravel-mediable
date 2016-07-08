<?php

namespace Frasmage\Mediable\UrlGenerators;

use Frasmage\Mediable\Media;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Abstract Url Generator
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
abstract class BaseUrlGenerator implements UrlGenerator
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Media
     */
    protected $media;

    /**
     * Constructor
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Set the media being operated on
     * @param Media $media
     */
    public function setMedia(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Get a config value for the current disk
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    protected function getDiskConfig($key, $default = null)
    {
        return $this->config->get("filesystems.disks.{$this->media->disk}.{$key}", $default);
    }
}
