<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\Helpers\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * URL Adapter.
 *
 * Adapts a string representing a URL
 */
class RemoteUrlAdapter extends StreamAdapter
{
    private Stream $streamHelper;
    protected string $url;

    public function __construct(string $source)
    {
        $this->url = $source;
        try {
            $stream = $this->connect($source, config('mediable.max_remote_url_redirects'));
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

    private function connect(string $url, int $maxRedirects): StreamInterface
    {
        if (config('mediable.validate_remote_url_redirects', true)) {
            return $this->walkRedirects($url, $maxRedirects);
        }

        // validate initial URL then blindly follow redirects
        $this->validateUrl($url);
        return Utils::streamFor(Utils::tryFopen($url, 'rb'));
    }

    private function walkRedirects(string $url, int $maxRedirects): StreamInterface
    {
        $this->validateUrl($url);

        $options = [
            'http' => [
                'method' => 'GET',
                'max_redirects' => 0,     // Stop PHP from following Location headers
                'ignore_errors' => true,  // Keep the stream open on 3xx/4xx/5xx responses
            ],
        ];
        $timeout = config('mediable.remote_url_timeout');
        if ($timeout !== null) {
            $options['http']['timeout'] = (float) $timeout;
        }
        $context = stream_context_create($options);
        $handle = Stream::tryFopen($url, 'rb', false, $context);

        if (!$handle) {
            throw ConfigurationException::invalidSource(
                "Failed to connect to URL: failed to connect to remote resource."
            );
        }
        // Inspect response headers (including the 'Location' header if redirected)
        $headers = stream_get_meta_data($handle)['wrapper_data'] ?? [];

        // extract status code
        preg_match('/HTTP\/\S+\s+(\d{3})/', $headers[0] ?? '', $matches);
        $statusCode = isset($matches[1]) ? (int)$matches[1] : null;

        // handle error codes
        if ($statusCode >= 400) {
            fclose($handle);
            throw ConfigurationException::invalidSource(
                "Failed to connect to URL: received $statusCode response code."
            );
        }

        // Handle redirect codes (301, 302, 303, 307, 308)
        if ($statusCode >= 300) {
            fclose($handle);
            // 2. Find the Location header (case-insensitive)
            $redirectUrl = null;
            foreach ($headers as $header) {
                if (stripos($header, 'Location:') === 0) {
                    $redirectUrl = trim(substr($header, 9));
                    break;
                }
            }
            if (empty($redirectUrl)) {
                throw ConfigurationException::invalidSource(
                    "Failed to connect to URL: remote URL received $statusCode response code, but no Location header."
                );
            }
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
            return $this->walkRedirects($redirectUrl, $maxRedirects - 1);
        }

        return Utils::streamFor($handle);
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
