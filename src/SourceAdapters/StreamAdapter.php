<?php

namespace Plank\Mediable\SourceAdapters;

use Plank\Mediable\Stream;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Psr\Http\Message\StreamInterface;

/**
 * Stream Adapter.
 *
 * Adapts a stream object or resource.
 */
abstract class StreamAdapter implements SourceAdapterInterface
{
    /**
     * The source object.
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $source;

    /**
     * The resource.
     * @var resource|null
     */
    protected $resource;

    /**
     * Constructor.
     * @param \Psr\Http\Message\StreamInterface|resource $source
     */
    public function __construct($source)
    {
        if (is_resource($source) && get_resource_type($source) === 'stream') {
            $this->resource = $source;
            $this->source = new Stream($source);
        } elseif ($source instanceof StreamInterface) {
            $this->source = $source;
        } else {
            throw ConfigurationException::unrecognizedSource($source);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        if ($this->resource) {
            return $this->resource;
        }

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
        return pathinfo($this->path(), PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    abstract public function mimeType();

    /**
     * {@inheritdoc}
     */
    public function contents()
    {
        return (string) $this->source;
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
        return $this->source->getSize();
    }

    /**
     * Get the stream wrapper data.
     *
     * @return array
     */
    protected function getWrapperData()
    {
        return $this->source->getMetadata('wrapper_data') ?: [];
    }

    /**
     * Get information about the resource.
     * @return array|bool
     */
    protected function getStats()
    {
        return fstat($this->source);
    }
}
