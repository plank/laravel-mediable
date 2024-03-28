<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use Psr\Http\Message\StreamInterface;

/**
 * Source Adapter Interface.
 *
 * Defines methods needed by the MediaUploader
 */
interface SourceAdapterInterface
{
    /**
     * Get the underlying source.
     * @return mixed
     */
    public function getSource(): mixed;

    /**
     * Get the absolute path to the file.
     * @return string
     */
    public function path(): string;

    /**
     * Get the name of the file.
     * @return string
     */
    public function filename(): string;

    /**
     * Get the extension of the file.
     * @return string
     */
    public function extension(): string;

    /**
     * Get the MIME type of the file.
     * @return string
     */
    public function mimeType(): string;

    /**
     * Return a stream if the original source can be converted to a stream.
     * If null is returned, the contents() method will be used instead.
     * Prevents needing to load the entire contents of the file into memory.
     */
    public function getStream(): ?StreamInterface;

    /**
     * Get the body of the file.
     * @return string
     */
    public function contents(): string;

    /**
     * Check if the file can be transferred.
     * @return bool
     */
    public function valid(): bool;

    /**
     * Determine the size of the file.
     * @return int
     */
    public function size(): int;
}
