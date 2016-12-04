<?php

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class ConfigurationException extends MediaUploadException
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
        if (is_object($source)) {
            $source = get_class($source);
        } elseif (is_resource($source)) {
            $source = get_resource_type($source);
        } else {
            $source = (string) $source;
        }

        return new static("Could not recognize source, `{$source}` provided.");
    }

    public static function diskNotFound($disk)
    {
        return new static("Cannot find disk named `{$disk}`.");
    }
}
