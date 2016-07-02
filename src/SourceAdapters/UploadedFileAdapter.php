<?php

namespace Frasmage\Mediable\SourceAdapters;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileAdapter implements SourceAdapterInterface
{

    /**
     * The source object
     * @var UploadedFile
     */
    protected $source;

    /**
     * Constructor
     * @param UploadedFile $source
     */
    public function __construct(UploadedFile $source)
    {
        $this->source = $source;
    }

    public function getSource(){
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
        return pathinfo($this->source->getClientOriginalName(), PATHINFO_FILENAME);
    }

    /**
     * {@inheritDoc}
     */
    public function extension()
    {
        return $this->source->getClientOriginalExtension();
    }

    /**
     * {@inheritDoc}
     */
    public function mimeType()
    {
        return $this->source->getClientMimeType();
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
        return $this->source->isValid();
    }

    /**
     * {@inheritDoc}
     */
    public function size()
    {
        return $this->source->getClientSize();
    }
}
