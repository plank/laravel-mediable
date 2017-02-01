<?php

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class FileNotFoundException extends MediaUploadException
{
    public static function fileNotFound(string $path)
    {
        return new static("File `{$path}` does not exist.");
    }
}
