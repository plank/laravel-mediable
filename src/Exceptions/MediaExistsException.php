<?php

namespace Plank\Mediable\Exceptions;

class MediaExistsException extends MediaUploadException
{
    public static function fileExists($path)
    {
        return new static("A file already exists at `{$path}`.");
    }
}
