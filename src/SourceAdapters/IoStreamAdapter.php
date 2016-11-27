<?php

namespace Plank\Mediable\SourceAdapters;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

/**
 * I/O Stream Adapter.
 *
 * Adapts a stream resource representing a php:// stream.
 */
class IoStreamAdapter extends StreamAdapter
{
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
        $guesser = ExtensionGuesser::getInstance();

        return $guesser->guess($this->mimeType());
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType()
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($this->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        $stats = $this->getStats();

        if (is_array($stats)) {
            return array_get($stats, 'size', 0);
        }

        return 0;
    }

    /**
     * Get the stream contents.
     * @return string
     */
    protected function toString()
    {
        $source = stream_get_contents($this->source);

        if ($this->isSeekable()) {
            rewind($this->source);
        }

        return $source;
    }
}
