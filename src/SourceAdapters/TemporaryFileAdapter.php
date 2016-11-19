<?php

namespace Plank\Mediable\SourceAdapters;

use Plank\Mediable\Helpers\TemporaryFile;

/**
 * File contents Adapter.
 *
 * Adapts the TemporaryFile class
 */
class TemporaryFileAdapter implements SourceAdapterInterface
{
    /**
     * The source object.
     * @var \Plank\Mediable\Helpers\TemporaryFile
     */
    protected $source;

    /**
     * Constructor.
     * @param \Plank\Mediable\Helpers\TemporaryFile $source
     */
    public function __construct(TemporaryFile $source)
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
        return $this->source->getRealPath();
    }

    /**
     * {@inheritdoc}
     */
    public function filename()
    {
        return $this->source->getOriginalName();
    }

    /**
     * {@inheritdoc}
     */
    public function extension()
    {
        return $this->source->guessExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType()
    {
        return $this->source->getMimeType();
    }

    /**
     * {@inheritdoc}
     */
    public function contents()
    {
        return $this->source->open();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->source->isFile();
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return $this->source->getSize();
    }
}
