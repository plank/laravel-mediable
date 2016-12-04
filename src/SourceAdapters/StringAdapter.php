<?php

namespace Plank\Mediable\SourceAdapters;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

/**
 * Blob/stream Adapter.
 *
 * Adapts a string representing file contents.
 */
class StringAdapter implements SourceAdapterInterface
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
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function filename()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function extension()
    {
        return $this->guessExtension();
    }

    /**
     * Returns the extension based on the mime type.
     *
     * If the mime type is unknown, returns null.
     *
     * This method uses the mime type as guessed by mimeType()
     * to guess the file extension.
     *
     * @return string|null The guessed extension or null if it cannot be guessed
     *
     * @see ExtensionGuesser
     * @see mimeType()
     */
    protected function guessExtension()
    {
        $type = $this->mimeType();
        $guesser = ExtensionGuesser::getInstance();

        return $guesser->guess($type);
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType()
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($this->source) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function contents()
    {
        $source = fopen('php://memory', 'w+b');

        fwrite($source, $this->source);
        rewind($source);

        return $source;
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
        return mb_strlen($this->source, '8bit');
    }
}
