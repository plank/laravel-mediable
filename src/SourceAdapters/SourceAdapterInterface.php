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
     */
    public function getSource(): mixed;

    /**
     * Get the absolute path to the file.
     */
    public function path(): string;

    /**
     * Get the name of the file.
     */
    public function filename(): string;

    /**
     * Get the extension of the file.
     */
    public function extension(): string;

    /**
     * Get the MIME type inferred from the contents of the file.
     */
    public function mimeType(): ?string;

    /**
     * Get the MIME type of the file as provided by the client.
     * This is not guaranteed to be accurate.
     */
    public function clientMimeType(): ?string;

    /**
     * Return a stream if the original source can be converted to a stream.
     * Prevents needing to load the entire contents of the file into memory.
     */
    public function getStream(): StreamInterface;

    /**
     * Get the body of the file.
     */
    public function contents(): string;

    /**
     * Check if the file can be transferred.
     */
    public function valid(): bool;

    /**
     * Determine the size of the file.
     */
    public function size(): int;
}
