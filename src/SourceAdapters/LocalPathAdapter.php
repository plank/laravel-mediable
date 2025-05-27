<?php
declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException;
use Plank\Mediable\Helpers\File;
use Psr\Http\Message\StreamInterface;

/**
 * Local Path Adapter.
 *
 * Adapts a string representing an absolute path
 */
class LocalPathAdapter extends StreamAdapter
{
    protected string $filePath;

    public function __construct(string $source)
    {
        $this->filePath = $source;
        if (!is_file($source) || !is_readable($source)) {
            throw ConfigurationException::invalidSource(
                "File not found {$source}"
            );
        }
        parent::__construct(
            Utils::streamFor(Utils::tryFopen($source, 'rb'))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function path(): ?string
    {
        return $this->filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): ?string
    {
        return pathinfo($this->filePath, PATHINFO_FILENAME) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): ?string
    {
        return pathinfo($this->filePath, PATHINFO_EXTENSION) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(): string
    {
        return mime_content_type($this->filePath);
    }

    public function clientMimeType(): ?string
    {
        return null;
    }
}
