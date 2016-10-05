<?php

namespace Plank\Mediable\UrlGenerators;

use Plank\Mediable\Media;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Abstract Url Generator.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
abstract class BaseUrlGenerator implements UrlGeneratorInterface
{
    /**
     * Configuration Repository.
     * @var Config
     */
    protected $config;

    /**
     * Media instance being linked.
     * @var Media
     */
    protected $media;

    /**
     * Constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Set the media being operated on.
     * @param Media $media
     */
    public function setMedia(Media $media)
    {
        $this->media = $media;
    }

    /**
     * {@inheritdoc}
     */
    public function isPubliclyAccessible()
    {
        return $this->getDiskConfig('visibility', 'private') == 'public';
    }

    /**
     * Get a config value for the current disk.
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    protected function getDiskConfig($key, $default = null)
    {
        return $this->config->get("filesystems.disks.{$this->media->disk}.{$key}", $default);
    }
}
