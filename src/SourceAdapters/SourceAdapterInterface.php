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
     * Get the name of the file.
     *
     * @return string|null Returns null if the file name cannot be determined.
     */
    public function filename(): ?string;

    /**
     * Get the extension of the file.
     *
     * @return string|null Returns null if the extension cannot be determined.
     */
    public function extension(): ?string;

    /**
     * Get the MIME type inferred from the contents of the file.
     */
    public function mimeType(): string;

    /**
     * Get the MIME type of the file as provided by the client.
     * This is not guaranteed to be accurate.
     *
     * @return string|null Returns null if no client MIME type is available.
     */
    public function clientMimeType(): ?string;

    /**
     * Return a stream if the original source can be converted to a stream.
     * Prevents needing to load the entire contents of the file into memory.
     */
    public function getStream(): StreamInterface;

    /**
     * Determine the size of the file.
     */
    public function size(): int;

    /**
     * Retrieve the md5 hash of the file.
     *
     * @param string $algo
     */
    public function hash(string $algo = 'md5'): string;
}
