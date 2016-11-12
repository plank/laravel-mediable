<?php

namespace Plank\Mediable\Exceptions;

use Exception;

/**
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaUploadException extends Exception
{
    const CANNOT_SET_ADAPTER = 101;
    const CANNOT_SET_MODEL = 102;
    const UNRECOGNIZED_SOURCE = 103;
    const NO_SOURCE_PROVIDED = 104;
    const DISK_NOT_FOUND = 105;
    const DISK_NOT_ALLOWED = 106;
    const FILE_NOT_FOUND = 107;
    const FILE_EXISTS = 108;
    const STRICT_TYPE_MISMATCH = 109;
    const UNRECOGNIZED_FILE_TYPE = 110;
    const MIME_RESTRICTED = 111;
    const EXTENSION_RESTRICTED = 112;
    const AGGREGATE_TYPE_RESTRICTED = 113;
    const FILE_IS_TOO_BIG = 114;

    public static function cannotSetAdapter($class)
    {
        return new static("Could not set adapter of class `{$class}`. Must implement `\Plank\Mediable\SourceAdapters\SourceAdapterInterface`.", 101);
    }

    public static function cannotSetModel($class)
    {
        return new static("Could not set `{$class}` as Media model class. Must extend `\Plank\Mediable\Media`.", 102);
    }

    public static function unrecognizedSource($source)
    {
        $source = is_object($source) ? get_class($source) : (string) $source;

        return new static("Could not recognize source, `{$source}` provided.", 103);
    }

    public static function noSourceProvided()
    {
        return new static('No source provided for upload.', 104);
    }

    public static function diskNotFound($disk)
    {
        return new static("Cannot find disk named `{$disk}`.", 105);
    }

    public static function diskNotAllowed($disk)
    {
        return new static("The disk `{$disk}` is not in the allowed disks for media.", 106);
    }

    public static function fileNotFound($path)
    {
        return new static("File `{$path}` does not exist.", 107);
    }

    public static function fileExists($path)
    {
        return new static("A file already exists at `{$path}`.", 108);
    }

    public static function strictTypeMismatch($mime, $ext)
    {
        return new static("File with mime of `{$mime}` not recognized for extension `{$ext}`.", 109);
    }

    public static function unrecognizedFileType($mime, $ext)
    {
        return new static("File with mime of `{$mime}` and extension `{$ext}` is not recognized.", 110);
    }

    public static function mimeRestricted($mime, $allowed_mimes)
    {
        $allowed = implode('`, `', $allowed_mimes);

        return new static("Cannot upload file with MIME type `{$mime}`. Only the `{$allowed}` MIME type(s) are permitted.", 111);
    }

    public static function extensionRestricted($extension, $allowed_extensions)
    {
        $allowed = implode('`, `', $allowed_extensions);

        return new static("Cannot upload file with extension `{$extension}`. Only the `{$allowed}` extension(s) are permitted.", 112);
    }

    public static function aggregateTypeRestricted($type, $allowed_types)
    {
        $allowed = implode('`, `', $allowed_types);

        return new static("Cannot upload file of aggregate type `{$type}`. Only files of type(s) `{$allowed}` are permitted.", 113);
    }

    public static function fileIsTooBig($size, $max)
    {
        return new static("File is too big ({$size} bytes). Maximum upload size is {$max} bytes.", 114);
    }
}
