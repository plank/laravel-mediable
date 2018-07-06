<?php

namespace Plank\Mediable\SourceAdapters;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Uploaded File Adapter.
 *
 * Adapts the UploadedFile class from Symfony Components.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class UploadedFileAdapter implements SourceAdapterInterface
{
    /**
     * The source object.
     * @var \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected $source;

    /**
     * Constructor.
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $source
     */
    public function __construct(UploadedFile $source)
    {
        $this->source = $source;
    }

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
        return pathinfo((string) $this->source->getClientOriginalName(), PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function extension()
    {
        $extension = $this->source->getClientOriginalExtension();

        if ($extension) {
            return $extension;
        }

        return (string) $this->source->guessExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType()
    {
        return (string) $this->source->getClientMimeType();
    }

    /**
     * {@inheritdoc}
     */
    public function contents()
    {
        return (string) file_get_contents($this->path());
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->source->isValid();
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return (int) $this->source->getClientSize();
    }
}
