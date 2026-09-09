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
        $this->validateUrl($source, config('mediable.max_remote_url_redirects'));
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

    private function validateUrl(string $url, int $maxRedirects): void
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
            $matched = false;
            foreach ($allowedHosts as $allowedHost) {
                if (fnmatch(strtolower($allowedHost), strtolower($host))) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                throw ConfigurationException::invalidSource(
                    'Remote URL host is not in the allowlist.'
                );
            }
        } else {
            // If no allowed hosts are specified, automatically prevent private IPs
            $ip = gethostbyname($host);
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

        if (config('mediable.validate_remote_url_redirects', true)) {
            // Make a header-only request to check for a redirect
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD (headers only)
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $ch,
                CURLOPT_FOLLOWLOCATION,
                false
            ); // DO NOT follow automatically
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            curl_close($ch);

            // Handle redirect codes (301, 302, 303, 307, 308)
            if ($statusCode >= 300 && $statusCode < 400 && !empty($redirectUrl)) {
                if ($maxRedirects < 1) {
                    throw ConfigurationException::invalidSource(
                        'Too many redirects for a remote URL.'
                    );
                }
                // Resolve relative paths if the redirect header isn't an absolute URL
                if (!str_contains($redirectUrl, '://')) {
                    $parts = parse_url($url);
                    $redirectUrl = $parts['scheme'] . '://' . $parts['host'] . '/' . ltrim(
                        $redirectUrl,
                        '/'
                    );
                }
                $this->validateUrl($redirectUrl, $maxRedirects - 1);
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
