<?php

namespace Plank\Mediable\SourceAdapters;

use Plank\Mediable\Helpers\File;

/**
 * URL Adapter.
 *
 * Adapts a string representing a URL
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class RemoteUrlAdapter implements SourceAdapterInterface
{
    /**
     * Cache of headers loaded from the remote server.
     * @var array
     */
    private $headers;

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
        return $this->getHeader('Content-Type');
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
        return strpos($this->getHeader(0), '200') !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return $this->getHeader('Content-Length');
    }

    /**
     * Read the headers of the remote content.
     * @param  string $key Header name
     * @return mixed
     */
    private function getHeader($key)
    {
        if (! $this->headers) {
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
    }
}
