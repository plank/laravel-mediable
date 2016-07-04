<?php

namespace Frasmage\Mediable\Exceptions;

use Exception;

/**
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaUploadException extends Exception
{
    public static function cannotSetAdapter($class)
    {
        return new static("Could not set adapter of class `{$class}`. Must implement `\Frasmage\Mediable\SourceAdapters\SourceAdapterInterface`.");
    }

    public static function cannotSetModel($class)
    {
        return new static("Could not set `{$class}` as Media model class. Must extend `\Frasmage\Mediable\Media`.");
    }

    public static function unrecognizedSource($source)
    {
        $source = is_object($source) ? get_class($source) : (string)$source;
        return new static("Could not recognize source, `{$source}` provided.");
    }

    public static function noSourceProvided()
    {
        return new static("No source provided for upload.");
    }

    public static function diskNotFound($disk)
    {
        return new static("Cannot find disk named `{$disk}`.");
    }

    public static function diskNotAllowed($disk)
    {
        return new static("The disk `{$disk}` is not in the allowed disks for media.");
    }

    public static function fileNotFound($path)
    {
        return new static("File `{$path}` does not exist.");
    }

    public static function fileExists($path)
    {
        return new static("A file already exists at `{$path}`.");
    }

    public static function strictTypeMismatch($mime, $ext)
    {
        return new static("File with mime of `{$mime}` not recognized for extension `{$ext}`.");
    }

    public static function unrecognizedFileType($mime, $ext)
    {
        return new static("File with mime of `{$mime}` and extension `{$ext}` is not permitted.");
    }

    public static function mimeRestricted($mime, $allowed_mimes)
    {
        $allowed = implode("`, `", $allowed_mimes);
        return new static("Cannot upload file with MIME type `{$mime}`. Only the `{$allowed}` MIME type(s) are permitted.");
    }

    public static function extensionRestricted($extension, $allowed_extensions)
    {
        $allowed = implode("`, `", $allowed_extensions);
        return new static("Cannot upload file with extension `{$extension}`. Only the `{$allowed}` extension(s) are permitted.");
    }

    public static function typeRestricted($type, $allowed_types)
    {
        $allowed = implode("`, `", $allowed_types);
        return new static("Cannot upload file of media type `{$type}`. Only files of type(s) `{$allowed}` are permitted.");
    }

    public static function fileIsTooBig($size, $max)
    {
        return new static("File is too big ({$size} bytes). Maximum upload size is {$max} bytes.");
    }
}
