<?php

namespace Plank\Mediable\SourceAdapters;

/**
 * Stream Adapter.
 *
 * Adapts a stream resource representing a file.
 */
abstract class StreamAdapter implements SourceAdapterInterface
{
    /**
     * The source object.
     * @var resource
     */
    protected $source;

    /**
     * The resource metadata.
     * @var array
     */
    protected $metadata = [];

    /**
     * Constructor.
     * @param resource $source
     */
    public function __construct($source)
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
        return $this->getMedatata('uri');
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
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->isStream();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function size();

    /**
     * Check if the stream is seekable.
     * @return bool
     */
    protected function isSeekable()
    {
        return $this->getMedatata('seekable');
    }

    /**
     * Get the stream wrapper data.
     *
     * @return array
     */
    protected function getWrapperData()
    {
        return $this->getMedatata('wrapper_data', []);
    }

    /**
     * Get a metadata value by key.
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    protected function getMedatata($key, $default = null)
    {
        if (empty($this->metadata)) {
            $this->metadata = stream_get_meta_data($this->source);
        }

        return array_get($this->metadata, $key, $default);
    }

    /**
     * Get information about the resource.
     * @return array|bool
     */
    protected function getStats()
    {
        return fstat($this->source);
    }

    /**
     * Check if the source is a stream resource.
     * @return bool
     */
    protected function isStream()
    {
        if (! is_resource($this->source)) {
            return false;
        }

        return get_resource_type($this->source) === 'stream';
    }
}
