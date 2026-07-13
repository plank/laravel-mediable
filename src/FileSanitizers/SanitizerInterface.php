<?php

namespace Plank\Mediable\FileSanitizers;

use Plank\Mediable\Media;
use Plank\Mediable\SourceAdapters\SourceAdapterInterface;
use Psr\Http\Message\StreamInterface;

interface SanitizerInterface
{
    /**
     * Whether the sanitizer is able to process the file type
     * @param string $mimeType
     * @param string $extension
     * @param string $aggregateType
     * @return bool
     */
    public function isApplicable(string $mimeType, string $extension, string $aggregateType): bool;

    /**
     * Modify the file and return a new source adapter with the modified file
     * @param StreamInterface $source
     * @return null|StreamInterface return null if no changes were made to the file
     */
    public function sanitize(StreamInterface $source): ?StreamInterface;
}
