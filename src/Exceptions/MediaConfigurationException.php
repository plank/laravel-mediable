<?php

namespace Plank\Mediable\Exceptions;

class MediaConfigurationException extends MediaUploadException
{
    public static function cannotSetAdapter($class)
    {
        return new static("Could not set adapter of class `{$class}`. Must implement `\Plank\Mediable\SourceAdapters\SourceAdapterInterface`.");
    }

    public static function cannotSetModel($class)
    {
        return new static("Could not set `{$class}` as Media model class. Must extend `\Plank\Mediable\Media`.");
    }

    public static function noSourceProvided()
    {
        return new static('No source provided for upload.');
    }

    public static function unrecognizedSource($source)
    {
        $source = is_object($source) ? get_class($source) : (string) $source;

        return new static("Could not recognize source, `{$source}` provided.");
    }

    public static function diskNotFound($disk)
    {
        return new static("Cannot find disk named `{$disk}`.");
    }
}
