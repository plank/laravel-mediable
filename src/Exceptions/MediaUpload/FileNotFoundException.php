<?php
declare(strict_types=1);

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class FileNotFoundException extends MediaUploadException
{
    public static function fileNotFound(string $path, ?\Throwable $original): self
    {
        return new self("File `{$path}` does not exist.", 0, $original);
    }

    public static function invalidDataUrl(): self
    {
        return new self('Invalid Data URL');
    }
}
