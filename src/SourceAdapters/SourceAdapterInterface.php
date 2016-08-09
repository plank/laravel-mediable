<?php

namespace Plank\Mediable\SourceAdapters;

/**
 * Source Adapter Interface.
 *
 * Defines methods needed by the MediaUploader
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
interface SourceAdapterInterface
{
    /**
     * Get the underlying source.
     * @return mixed
     */
    public function getSource();

    /**
     * Get the absolute path to the file.
     * @return string
     */
    public function path();

    /**
     * Get the name of the file.
     * @return string
     */
    public function filename();

    /**
     * Get the extension of the file.
     * @return string
     */
    public function extension();

    /**
     * Get the MIME type of the file.
     * @return string
     */
    public function mimeType();

    /**
     * Get the body of the file.
     * @return string
     */
    public function contents();

    /**
     * Check if the file can be transfered.
     * @return bool
     */
    public function valid();

    /**
     * Determine the size of the file.
     * @return int
     */
    public function size();
}
