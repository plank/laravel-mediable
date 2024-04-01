<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;

/**
 * URL Adapter.
 *
 * Adapts a string representing a URL
 */
class RemoteUrlAdapter extends StreamAdapter
{
    /**
     * Cache of headers loaded from the remote server.
     */
    private array $headers;

    protected string $url;

    private bool $connected;

    public function __construct(string $source)
    {
        $this->url = $source;
        try {
            $resource = Utils::tryFopen($source, 'rb');
            $stream = Utils::streamFor($resource);
            $this->connected = true;
        } catch (\RuntimeException $e) {
            $stream = Utils::streamFor('');
            $this->connected = false;
        }

        parent::__construct(
            $stream
        );
    }

    /**
     * {@inheritdoc}
     */
    public function path(): ?string
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): ?string
    {
        return pathinfo(
            parse_url($this->url, PHP_URL_PATH),
            PATHINFO_FILENAME
        ) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): ?string
    {
        return pathinfo(
            parse_url($this->url, PHP_URL_PATH),
            PATHINFO_EXTENSION
        ) ?: null;
    }

    public function valid(): bool
    {
        return $this->connected && parent::valid();
    }
}
