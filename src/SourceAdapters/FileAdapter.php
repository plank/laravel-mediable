<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Helpers\File as FileHelper;
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
    public function getSource(): mixed
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function path(): string
    {
        return $this->source->getPath() . '/' . $this->source->getFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): string
    {
        return pathinfo($this->source->getFilename(), PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): string
    {
        $extension = pathinfo($this->path(), PATHINFO_EXTENSION);

        if ($extension) {
            return $extension;
        }

        return (string)FileHelper::guessExtension($this->mimeType());
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(): string
    {
        return (string)$this->source->getMimeType();
    }

    /**
     * {@inheritdoc}
     */
    public function contents(): string
    {
        return (string)file_get_contents($this->path());
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
        return (int)filesize($this->path());
    }
}
