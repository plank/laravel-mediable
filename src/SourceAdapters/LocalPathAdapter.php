<?php

namespace Plank\Mediable\SourceAdapters;

use Plank\Mediable\Helpers\File;

/**
 * Local Path Adapter.
 *
 * Adapts a string representing an absolute path
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class LocalPathAdapter implements SourceAdapterInterface
{
    /**
     * The source string.
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

    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function path()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function filename()
    {
        return pathinfo($this->source, PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function extension()
    {
        $extension = pathinfo($this->source, PATHINFO_EXTENSION);

        if ($extension) {
            return $extension;
        }

        return File::guessExtension($this->mimeType());
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType()
    {
        return mime_content_type($this->source);
    }

    /**
     * {@inheritdoc}
     */
    public function contents()
    {
        return file_get_contents($this->source);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return is_readable($this->source);
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return filesize($this->source);
    }
}
