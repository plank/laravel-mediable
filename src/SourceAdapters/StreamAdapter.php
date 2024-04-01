<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\CachingStream;
use Psr\Http\Message\StreamInterface;

/**
 * Stream Adapter.
 *
 * Adapts a stream object or resource.
 */
class StreamAdapter implements SourceAdapterInterface
{
    const BUFFER_SIZE = 2048;

    private const TYPE_MEMORY = 'php';
    private const TYPE_DATA_URL = 'rfc2397';
    private const TYPE_HTTP = 'http';
    private const TYPE_FILE = 'plainfile';
    private const TYPE_FTP = 'ftp';

    protected StreamInterface $source;

    protected StreamInterface $originalSource;

    /**
     * The contents of the stream.
     * @var string
     */
    protected string $contents;

    protected int $size;

    protected string $hash;

    protected string $mimeType;

    /**
     * Constructor.
     * @param StreamInterface $source
     */
    public function __construct(StreamInterface $source)
    {
        $this->source = $this->originalSource = $source;
        if (!$this->source->isSeekable()) {
            $this->source = new CachingStream($this->source);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function path(): ?string
    {
        $type = $this->getStreamType();
        if (in_array($type, [self::TYPE_DATA_URL, self::TYPE_MEMORY])) {
            return null;
        }

        return $this->originalSource->getMetadata('uri');
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): ?string
    {
        $path = $this->path();
        if (!$path) {
            return null;
        }
        return pathinfo(
            parse_url($this->path(), PHP_URL_PATH) ?? '',
            PATHINFO_FILENAME
        ) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): ?string
    {
        if ($path = $this->path()) {
            $extension = pathinfo(
                parse_url($path, PHP_URL_PATH) ?? '',
                PATHINFO_EXTENSION
            );
            if ($extension) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(): string
    {
        if (!isset($this->mimeType)) {
            $this->scanFile();
        }

        return $this->mimeType;
    }

    public function clientMimeType(): ?string
    {
        // supported primarily by data URLs
        if ($mime = $this->originalSource->getMetadata('mediatype')) {
            return $mime;
        }

        if ($contentType = $this->getHttpHeader('Content-Type')) {
            $mime = explode(';', $contentType)[0];

            return $mime;
        }

        return null;
    }

    public function getStream(): StreamInterface
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        if ($this->getStreamType() === self::TYPE_HTTP) {
            $code = $this->getHttpResponseCode();
            if (!$code || $code < 200 || $code >= 300) {
                return false;
            }
        }
        return $this->originalSource->isReadable();
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        $size = $this->source->getSize();

        if (!is_null($size)) {
            return $size;
        }

        if (!isset($this->size)) {
            $this->scanFile();
        }

        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function hash(): string
    {
        if (!isset($this->hash)) {
            $this->scanFile();
        }
        return $this->hash;
    }

    /**
     * @return array|mixed|null
     */
    private function getStreamType(): mixed
    {
        return strtolower($this->originalSource->getMetadata('wrapper_type'));
    }

    private function getHttpHeader($headerName): ?string
    {
        if ($this->getStreamType() !== self::TYPE_HTTP) {
            return null;
        }

        $headers = $this->originalSource->getMetadata('wrapper_data');
        if ($headers) {
            foreach ($headers as $header) {
                if (stripos($header, "$headerName: ") === 0) {
                    return substr($header, strlen($headerName) + 2);
                }
            }
        }

        return null;
    }

    private function getHttpResponseCode(): ?int
    {
        if ($this->getStreamType() !== self::TYPE_HTTP) {
            return null;
        }
        $headers = $this->originalSource->getMetadata('wrapper_data');
        if (!empty($headers)
            && preg_match('/HTTP\/\d+\.\d+\s+(\d+)/i', $headers[0], $matches)
        ) {
            return (int)$matches[1];
        }

        return null;
    }

    private function scanFile(): void
    {
        $this->size = 0;
        $this->source->rewind();
        try {
            $hash = hash_init('md5');
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            while (!$this->source->eof()) {
                $buffer = $this->source->read(self::BUFFER_SIZE);
                if (!isset($this->mimeType)) {
                    $this->mimeType = finfo_buffer($finfo, $buffer);
                }
                hash_update($hash, $buffer);
                $this->size += strlen($buffer);
            }
            $this->hash = hash_final($hash);
            $this->source->rewind();
        } finally {
            if ($finfo) {
                finfo_close($finfo);
            }
        }
    }
}
