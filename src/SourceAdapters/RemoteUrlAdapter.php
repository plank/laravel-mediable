<?php

namespace Frasmage\Mediable\SourceAdapters;

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
        return strpos($this->getHeader(0), '200');
    }

    /**
     * {@inheritDoc}
     */
    public function size()
    {
        return $this->getHeader('Content-Length');
    }

    /**
     * [getHeader description]
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    private function getHeader($key)
    {
        if (!$this->headers) {
            $this->headers = get_headers($this->source, 1);
        }
        if (array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        }
        return null;
    }
}
