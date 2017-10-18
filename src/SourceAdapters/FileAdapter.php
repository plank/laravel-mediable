<?php

namespace Plank\Mediable\SourceAdapters;

use Symfony\Component\HttpFoundation\File\File;
use Plank\Mediable\Helpers\File as FileHelper;

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
     * @var \Symfony\Component\HttpFoundation\File\File
     */
    protected $source;

    /**
     * Constructor.
     * @param \Symfony\Component\HttpFoundation\File\File $source
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
        $extension = pathinfo($this->path(), PATHINFO_EXTENSION);

        if ($extension) {
            return $extension;
        }

        return FileHelper::guessExtension($this->mimeType());
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
        return file_get_contents($this->path());
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
