<?php
declare(strict_types=1);

namespace Plank\Mediable\Exceptions;

use Exception;

/**
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaUrlException extends Exception
{
    public static function generatorNotFound(string $disk, string $driver): self
    {
        return new static("Could not find UrlGenerator for disk `{$disk}` of type `{$driver}`");
    }

    public static function invalidGenerator(string $class): self
    {
        return new static("Could not set UrlGenerator, class `{$class}` does not extend `Plank\Mediable\UrlGenerators\UrlGenerator`");
    }

    public static function mediaNotPubliclyAccessible(string $path): self
    {
        $public_path = public_path();
        return new static("Media file `{$path}` is not part of the public path `{$public_path}`");
    }

    public static function cloudMediaNotPubliclyAccessible(string $disk): self
    {
        return new static("Media files on cloud disk `{$disk}` are not publicly accessible");
    }
}
