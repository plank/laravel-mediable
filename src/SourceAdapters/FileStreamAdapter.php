<?php

namespace Plank\Mediable\SourceAdapters;

/**
 * File Stream Adapter.
 *
 * Adapts a stream resource representing a file:// stream.
 */
class FileStreamAdapter extends StreamAdapter
{
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

        return array_get($stats, 'size', 0);
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
