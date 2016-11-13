<?php

namespace Plank\Mediable\Exceptions;

class MediaNotFoundException extends MediaUploadException
{
    public static function fileNotFound($path)
    {
        return new static("File `{$path}` does not exist.");
    }
}
