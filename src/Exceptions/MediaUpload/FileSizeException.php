<?php

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class FileSizeException extends MediaUploadException
{
    public static function fileIsTooBig($size, $max)
    {
        return new static("File is too big ({$size} bytes). Maximum upload size is {$max} bytes.");
    }
}
