<?php

namespace Frasmage\Mediable\SourceAdapters;

/**
 * URL Adapter
 *
 * Adapts a string representing a URL
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class RemoteUrlAdapter implements SourceAdapterInterface
{

    /**
     * Cache of headers loaded from the remote server
     * @var array
     */
    private $headers;

    /**
     * The source string
     * @var string
     */
    protected $source;

    /**
     * Constructor
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
     * {@inheritDoc}
     */
    public function path()
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function filename()
    {
        return pathinfo($this->source, PATHINFO_FILENAME);
    }

    /**
     * {@inheritDoc}
     */
    public function extension()
    {
        return pathinfo($this->source, PATHINFO_EXTENSION);
    }

    /**
     * {@inheritDoc}
     */
    public function mimeType()
    {
        return $this->getHeader('Content-Type');
    }

    /**
     * {@inheritDoc}
     */
    public function contents()
    {
        return fopen($this->source, 'r');
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return strpos($this->getHeader(0), '200') !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function size()
    {
        return $this->getHeader('Content-Length');
    }

    /**
     * Read the headers of the remote content
     * @param  string $key Header name
     * @return mixed
     */
    private function getHeader($key)
    {
        if (!$this->headers) {
            $this->headers = get_headers($this->source, 1);
        }
        if (array_key_exists($key, $this->headers)) {
            //if redirects encountered, return the final values
            if (is_array($this->headers[$key])) {
                return end($this->headers[$key]);
            } else {
                return $this->headers[$key];
            }
        }
        return null;
    }
}
