<?php

namespace Frasmage\Mediable\Exceptions;

use Exception;

class MediaUploadException extends Exception
{
    public static function unrecognizedSource($source)
    {
        $source = is_object($source) ? get_class($source) : (string)$source;
        return new static("Could not recognize source, `{$source}` provided");
    }

    public static function CannotSetAdapter($class)
    {
        return new static("Cannot set adapter of class `{$class}`. Adapter must implement `\Frasmage\Mediable\SourceAdapter\SourceAdapterInterface`.");
    }

    public static function fileNotFound($path)
    {
        return new static("File `{$path}` does not exist.");
    }

    public static function strictTypeMismatch($mime, $ext)
    {
        return new static("File with mime of `{$mime}` not recognized for extension `{$ext}`.");
    }

    public static function unrecognizedFileType($mime, $ext)
    {
        return new static("File with mime of `{$mime}` and extension `{$ext}` is not permitted.");
    }

    public static function typeRestricted($type, $allowed_types)
    {
        $allowed = implode("`, `", $allowed_types);
        return new static("Cannot upload file of type `{$type}`. Only files of type(s) `{$allowed}` are permitted.");
    }

    public static function fileIsTooBig($size)
    {
        $size = bytestring($size);
        $max = bytestring(config('admin.media.max_size'));
        return new static("File is too big ({$size}). Maximum upload size is {$max}.");
    }

    public static function missingWritePermissions($disk, $path)
    {
        return new static("Unable to write to `{$path}` on filesystem `{$disk}`.");
    }
}
