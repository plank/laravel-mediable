<?php

namespace Plank\Mediable\SourceAdapters;

use Symfony\Component\HttpFoundation\File\File;

/**
 * File Adapter
 *
 * Adapts the File class from Symfony Components
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class FileAdapter implements SourceAdapter
{

    /**
     * The source object
     * @var File
     */
    protected $source;

    /**
     * Constructor
     * @param File $source
     */
    public function __construct(File $source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function path()
    {
        return $this->source->getPath().'/'.$this->source->getFilename();
    }

    /**
     * {@inheritDoc}
     */
    public function filename()
    {
        return pathinfo($this->source->getFilename(), PATHINFO_FILENAME);
    }

    /**
     * {@inheritDoc}
     */
    public function extension()
    {
        return pathinfo($this->source->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * {@inheritDoc}
     */
    public function mimeType()
    {
        return $this->source->getMimeType();
    }

    /**
     * {@inheritDoc}
     */
    public function contents()
    {
        return fopen($this->path(), 'r');
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return file_exists($this->path());
    }

    /**
     * {@inheritDoc}
     */
    public function size()
    {
        return filesize($this->path());
    }
}
