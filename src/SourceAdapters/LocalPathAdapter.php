<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Helpers\File;
use Psr\Http\Message\StreamInterface;

/**
 * Local Path Adapter.
 *
 * Adapts a string representing an absolute path
 */
class LocalPathAdapter implements SourceAdapterInterface
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
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): ?string
    {
        return pathinfo($this->source, PATHINFO_FILENAME) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): ?string
    {
        return pathinfo($this->source, PATHINFO_EXTENSION) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(): string
    {
        return mime_content_type($this->source);
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
        return Utils::streamFor(fopen($this->path(), 'rb'));
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return is_readable($this->source);
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return filesize($this->source) ?: 0;
    }

    public function hash(): string
    {
        return md5_file($this->source) ?: '';
    }
}
