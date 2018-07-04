<?php

namespace Plank\Mediable\SourceAdapters;

use Plank\Mediable\Helpers\File;

/**
 * Raw content Adapter.
 *
 * Adapts a string representing raw contents.
 */
class RawContentAdapter implements SourceAdapterInterface
{
    /**
     * The source object.
     * @var string
     */
    protected $source;

    /**
     * Constructor.
     * @param string $source
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
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function filename()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function extension()
    {
        return (string) File::guessExtension($this->mimeType());
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType()
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return (string) $finfo->buffer($this->source);
    }

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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return (int) mb_strlen($this->source, '8bit');
    }
}
