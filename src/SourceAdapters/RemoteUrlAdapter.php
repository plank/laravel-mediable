<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;

/**
 * URL Adapter.
 *
 * Adapts a string representing a URL
 */
class RemoteUrlAdapter extends StreamAdapter
{
    protected string $url;

    public function __construct(string $source)
    {
        $this->url = $source;
        try {
            $resource = Utils::tryFopen($source, 'rb');
            $stream = Utils::streamFor($resource);
        } catch (\RuntimeException $e) {
            throw ConfigurationException::invalidSource(
                "Failed to connect to URL: {$e->getMessage()}",
                $e
            );
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
}
