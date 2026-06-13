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
        $this->validateUrl($source);
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

    private function validateUrl(string $url): void
    {
        $allowedSchemes = (array) config('mediable.allowed_remote_schemes', ['https']);
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? '';
        $host = $parsed['host'] ?? '';
        if (empty($host) || empty($scheme)) {
            throw ConfigurationException::invalidSource(
                'Remote URL must include a valid scheme and host.'
            );
        }

        if (!empty($allowedSchemes) && !in_array($scheme, $allowedSchemes, true)) {
            throw ConfigurationException::invalidSource(
                "Remote URL scheme '{$parsed['scheme']}' is not allowed."
            );
        }

        $allowedHosts = (array) config('mediable.allowed_remote_hosts', []);
        if (!empty($allowedHosts)) {
            foreach ($allowedHosts as $allowedHost) {
                if (fnmatch(strtolower($allowedHost), strtolower($host))) {
                    return; // Host is allowed, exit validation
                }
            }
            throw ConfigurationException::invalidSource(
                'Remote URL host is not in the allowlist.'
            );
        } else {
            // If no allowed hosts are specified, automatically prevent private IPs
            $ip  = gethostbyname($host);
            $isPublic = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
            if (!$isPublic) {
                throw ConfigurationException::invalidSource(
                    'Private IP ranges are not permitted for remote URLs.'
                );
            }
        }
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
