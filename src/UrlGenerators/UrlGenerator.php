<?php

namespace Frasmage\Mediable\UrlGenerators;

use Frasmage\Mediable\Media;

/**
 * Url Generator Interface
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
interface UrlGenerator
{
    /**
     * Set the media instance for which urls are being generated
     * @param Media $media
     */
    public function setMedia(Media $media);

    /**
     * Retrieve the absolute path to the file
     * @return string
     */
    public function getAbsolutePath();

    /**
     * Check if the file is publicly accessible
     * @return boolean
     */
    public function isPubliclyAccessible();

    /**
     * Get a Url to the file
     * @return string
     */
    public function getUrl();
}
