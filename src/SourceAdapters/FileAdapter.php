<?php

declare(strict_types=1);

namespace Plank\Mediable\SourceAdapters;

use GuzzleHttp\Psr7\Utils;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * File Adapter.
 *
 * Adapts the File class from Symfony Components
 */
class FileAdapter extends StreamAdapter
{
    protected File $file;

    public function __construct(File $source)
    {
        $this->file = $source;
        $path = $source->getRealPath();
        if ($path === false) {
            throw ConfigurationException::invalidSource(
                "File not found {$source->getPathname()}"
            );
        }
        parent::__construct(
            Utils::streamFor(
                Utils::tryFopen($path, 'rb')
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function filename(): ?string
    {
        return pathinfo($this->file->getRealPath(), PATHINFO_FILENAME) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function extension(): ?string
    {
        return pathinfo($this->file->getRealPath(), PATHINFO_EXTENSION) ?: null;
    }

    public function clientMimeType(): ?string
    {
        return null;
    }
}
