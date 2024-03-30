<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * File Adapter.
 *
 * Adapts the File class from Symfony Components
 */
class FileAdapter implements SourceAdapterInterface
{
    protected File $source;

    public function __construct(File $source)
    {
        $this->source = $source;
    }

    /**
     * {@inheritdoc}
     */
    public function path(): ?string
    {
        return $this->source->getPath() . '/' . $this->source->getFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): ?string
    {
        return pathinfo($this->source->getFilename(), PATHINFO_FILENAME) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): ?string
    {
        return pathinfo($this->path(), PATHINFO_EXTENSION) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(): string
    {
        return (string)$this->source->getMimeType();
    }

    public function clientMimeType(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
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
        return file_exists($this->path());
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return filesize($this->path()) ?: 0;
    }

    public function hash(): string
    {
        return md5_file($this->path()) ?: '';
    }
}
