<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\MimeType;
use Plank\Mediable\Helpers\File;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Mime\MimeTypes;

/**
 * Stream Adapter.
 *
 * Adapts a stream object or resource.
 */
class StreamAdapter implements SourceAdapterInterface
{
    const BUFFER_SIZE = 1024;

    private const TYPE_MEMORY = 'PHP';
    private const TYPE_DATA_URL = 'RFC2397';
    private const TYPE_HTTP = 'http';
    private const TYPE_FILE = 'plainfile';

    protected StreamInterface $source;

    /**
     * The contents of the stream.
     * @var string
     */
    protected string $contents;

    /**
     * Constructor.
     * @param StreamInterface $source
     */
    public function __construct(StreamInterface $source)
    {
        $this->source = $source;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource(): mixed
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function path(): string
    {
        $type = $this->getStreamType();
        if ($type == self::TYPE_DATA_URL || $type == self::TYPE_MEMORY) {
            return '';
        }

        return (string)$this->source->getMetadata('uri');
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): string
    {
        return pathinfo(parse_url($this->path(), PHP_URL_PATH) ?? '', PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): string
    {
        $extension = pathinfo(
            parse_url($this->path(), PHP_URL_PATH) ?? '',
            PATHINFO_EXTENSION
        );

        if ($extension) {
            return $extension;
        }

        $mimeType = $this->mimeType() ?? $this->clientMimeType();
        if ($mimeType) {
            return (string)File::guessExtension($mimeType);
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(): ?string
    {
        $type = $this->getStreamType();
        if ($type == self::TYPE_FILE) {
            return MimeTypes::getDefault()->guessMimeType(
                $this->source->getMetadata('uri')
            );
        }

        if ($type == self::TYPE_MEMORY || $type == self::TYPE_DATA_URL) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $this->contents());
            finfo_close($finfo);
            return $mimeType;
        }

        return null;
    }

    public function clientMimeType(): ?string
    {
        $type = $this->getStreamType();

        // supported primarily by data URLs
        if ($this->source->getMetadata('mediatype')) {
            return $this->source->getMetadata('mediatype');
        }

        if ($type == self::TYPE_HTTP) {
            $headers = $this->source->getMetadata('wrapper_data');
            foreach ($headers as $header) {
                if (preg_match('/Content-Type:\s?(\w+\/[\.+\-\w]+)/i',$header, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function contents(): string
    {
        if ($this->source->isSeekable()) {
            return (string)$this->source;
        }
        if (!isset($this->contents)) {
            $this->contents = $this->source->getContents();
        }
        return $this->contents;
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
        return $this->source->isReadable();
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

        if ($this->source->isSeekable()) {
            $this->source->rewind();
            $size = 0;
            while (!$this->source->eof()) {
                $size += (int)mb_strlen($this->source->read(self::BUFFER_SIZE), '8bit');
            }
            $this->source->rewind();
            return $size;
        }

        return (int)mb_strlen($this->contents(), '8bit');
    }

    /**
     * @return array|mixed|null
     */
    public function getStreamType(): mixed
    {
        return $this->source->getMetadata('wrapper_type');
    }
}
