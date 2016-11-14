<?php

namespace Plank\Mediable\UrlGenerators;

use Plank\Mediable\Media;

/**
 * Url Generator Interface.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
interface UrlGeneratorInterface
{
    /**
     * Set the media instance for which urls are being generated.
     * @param \Plank\Mediable\Media $media
     */
    public function setMedia(Media $media);

    /**
     * Retrieve the absolute path to the file.
     *
     * For local files this should return a path
     * For remote files this should return a url
     * @return string
     */
    public function getAbsolutePath();

    /**
     * Check if the file is publicly accessible.
     *
     * Disks configs should indicate this with the visibility key
     * @return bool
     */
    public function isPubliclyAccessible();

    /**
     * Get a Url to the file.
     * @return string
     */
    public function getUrl();
}
