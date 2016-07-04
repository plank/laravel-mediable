<?php

namespace Frasmage\Mediable\Exceptions;

use Exception;

/**
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaUrlException extends Exception
{
    public static function mediaNotPubliclyAccessible($path, $public_path)
    {
        return new static("Media file `{$path}` is not part of the public path `{$public_path}`");
    }

    public static function mediaNotGlideAccessible($path, $glide_path)
    {
        return new static("Media file `{$path}` is not part of Glide's source path `{$glide_path}`");
    }
}
