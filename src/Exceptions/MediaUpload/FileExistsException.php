<?php

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class FileExistsException extends MediaUploadException
{
    public static function fileExists($path)
    {
        return new static("A file already exists at `{$path}`.");
    }
}
