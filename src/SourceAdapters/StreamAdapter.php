<?php

namespace Plank\Mediable\SourceAdapters;

use Plank\Mediable\Helpers\File;
use Psr\Http\Message\StreamInterface;

/**
 * Stream Adapter.
 *
 * Adapts a stream object or resource.
 */
class StreamAdapter implements SourceAdapterInterface
{
    /**
     * The source object.
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $source;

    /**
     * The contents of the stream.
     * @var string
     */
    protected $contents;

    /**
     * Constructor.
     * @param \Psr\Http\Message\StreamInterface $source
     */
    public function __construct(StreamInterface $source)
    {
        $this->source = $source;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function path()
    {
        return $this->source->getMetadata('uri');
    }

    /**
     * {@inheritdoc}
     */
    public function filename()
    {
        return pathinfo($this->path(), PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function extension()
    {
        $extension = pathinfo($this->path(), PATHINFO_EXTENSION);

        if ($extension) {
            return $extension;
        }

        return File::guessExtension($this->mimeType());
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType()
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($this->contents());
    }

    /**
     * {@inheritdoc}
     */
    public function contents()
    {
        if (is_null($this->contents)) {
            if ($this->source->isSeekable()) {
                $this->contents = (string) $this->source;
            } else {
                $this->contents = (string) $this->source->getContents();
            }
        }

        return $this->contents;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->source->isReadable();
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        $size = $this->source->getSize();

        if (! is_null($size)) {
            return $size;
        }

        return mb_strlen($this->contents(), '8bit');
    }
}
