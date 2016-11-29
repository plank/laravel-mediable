<?php

namespace Plank\Mediable\SourceAdapters;

/**
 * File Stream Adapter.
 *
 * Adapts a stream object or resource representing a file:// stream.
 */
class FileStreamAdapter extends StreamAdapter
{
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

        return array_get($this->getStats(), 'size');
    }
}
