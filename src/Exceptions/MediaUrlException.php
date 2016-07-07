<?php

namespace Frasmage\Mediable\Exceptions;

use Exception;

/**
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaUrlException extends Exception
{
    public static function generatorNotFound($disk, $driver)
    {
        return new static("Could not find UrlGenerator for disk `{$disk}` of type `{$driver}`");
    }

    public static function invalidGenerator($class)
    {
        return new static("Could not set UrlGenerator, class `{$class}` does not extend `Frasmage\Mediable\UrlGenerators\UrlGenerator`");
    }

    public static function mediaNotPubliclyAccessible($path, $public_path)
    {
        return new static("Media file `{$path}` is not part of the public path `{$public_path}`");
    }

    public static function cannotGetAbsolutePath($disk)
    {
        return new static("Cannot get absolute path. Disk `{$disk}` is not on the local filesystem");
    }
}
