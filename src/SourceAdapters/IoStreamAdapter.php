<?php

namespace Plank\Mediable\SourceAdapters;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

/**
 * I/O Stream Adapter.
 *
 * Adapts a stream object or resource representing a php:// stream.
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

        return $finfo->buffer((string) $this->source);
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        $size = $this->source->getSize();

        if (! is_null($size)) {
            return $size;
        }

        $stats = $this->getStats();

        if (is_array($stats)) {
            return array_get($stats, 'size');
        }
    }
}
