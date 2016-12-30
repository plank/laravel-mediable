<?php

namespace Plank\Mediable\SourceAdapters;

use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\Stream;

/**
 * Stream resource Adapter.
 *
 * Adapts a stream resource.
 */
class StreamResourceAdapter extends StreamAdapter
{
    /**
     * The resource.
     * @var resource|null
     */
    protected $resource;

    /**
     * Constructor.
     * @param resource $source
     */
    public function __construct($source)
    {
        if (! is_resource($source) || get_resource_type($source) !== 'stream') {
            throw ConfigurationException::unrecognizedSource($source);
        }

        $this->source = new Stream($source);

        $this->resource = $source;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return $this->resource;
    }
}
