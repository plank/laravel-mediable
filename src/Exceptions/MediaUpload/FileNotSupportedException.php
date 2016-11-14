<?php

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class FileNotSupportedException extends MediaUploadException
{
    public static function strictTypeMismatch($mime, $ext)
    {
        return new static("File with mime of `{$mime}` not recognized for extension `{$ext}`.");
    }

    public static function unrecognizedFileType($mime, $ext)
    {
        return new static("File with mime of `{$mime}` and extension `{$ext}` is not recognized.");
    }

    public static function mimeRestricted($mime, $allowed_mimes)
    {
        $allowed = implode('`, `', $allowed_mimes);

        return new static("Cannot upload file with MIME type `{$mime}`. Only the `{$allowed}` MIME type(s) are permitted.");
    }

    public static function extensionRestricted($extension, $allowed_extensions)
    {
        $allowed = implode('`, `', $allowed_extensions);

        return new static("Cannot upload file with extension `{$extension}`. Only the `{$allowed}` extension(s) are permitted.");
    }

    public static function aggregateTypeRestricted($type, $allowed_types)
    {
        $allowed = implode('`, `', $allowed_types);

        return new static("Cannot upload file of aggregate type `{$type}`. Only files of type(s) `{$allowed}` are permitted.");
    }
}
