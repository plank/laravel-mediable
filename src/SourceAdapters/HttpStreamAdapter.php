<?php

namespace Plank\Mediable\SourceAdapters;

/**
 * HTTP Stream Adapter.
 *
 * Adapts a stream object or resource representing an http:// stream.
 */
class HttpStreamAdapter extends StreamAdapter
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * {@inheritdoc}
     */
    public function mimeType()
    {
        return array_get($this->getHeaders(), 'Content-Type');
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

        return array_get($this->getHeaders(), 'Content-Length');
    }

    /**
     * Read the headers from the wrapper data.
     * @return array
     */
    protected function getHeaders()
    {
        if (empty($this->headers)) {
            $this->headers = array_reduce($this->getWrapperData(), function ($headers, $header) {
                if (strpos($header, 'HTTP') === 0) {
                    return $headers;
                }

                $field = explode(': ', $header);

                $name = implode('-', array_map('ucfirst', explode('-', $field[0])));

                $headers[$name] = $field[1];

                return $headers;
            }, []);
        }

        return $this->headers;
    }
}
