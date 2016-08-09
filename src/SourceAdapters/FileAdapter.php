<?php

namespace Plank\Mediable\SourceAdapters;

use Symfony\Component\HttpFoundation\File\File;

/**
 * File Adapter.
 *
 * Adapts the File class from Symfony Components
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class FileAdapter implements SourceAdapterInterface
{
    /**
     * The source object.
     * @var File
     */
    protected $source;

    /**
     * Constructor.
     * @param File $source
     */
    public function __construct(File $source)
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
        return $this->source->getPath().'/'.$this->source->getFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function filename()
    {
        return pathinfo($this->source->getFilename(), PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function extension()
    {
        return pathinfo($this->source->getFilename(), PATHINFO_EXTENSION);
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
        return fopen($this->path(), 'r');
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return file_exists($this->path());
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return filesize($this->path());
    }
}
