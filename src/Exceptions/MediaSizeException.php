<?php

namespace Plank\Mediable\Exceptions;

class MediaSizeException extends MediaUploadException
{
    public static function fileIsTooBig($size, $max)
    {
        return new static("File is too big ({$size} bytes). Maximum upload size is {$max} bytes.");
    }
}
