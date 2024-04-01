<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Helpers\File;
use Psr\Http\Message\StreamInterface;

/**
 * Raw content Adapter.
 *
 * Adapts a string representing raw contents.
 */
class RawContentAdapter implements SourceAdapterInterface
{
    protected string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    /**
     * {@inheritdoc}
     */
    public function path(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): ?string
    {
        return (string)File::guessExtension($this->mimeType());
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        return (string)$fileInfo->buffer($this->source);
    }

    public function clientMimeType(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(): StreamInterface
    {
        return Utils::streamFor($this->source);
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return mb_strlen($this->source, '8bit') ?: 0;
    }

    public function hash(): string
    {
        return md5($this->source) ?: '';
    }
}
