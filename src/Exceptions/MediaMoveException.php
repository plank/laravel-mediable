<?php

namespace Plank\Mediable\Exceptions;

use Exception;

/**
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaMoveException extends Exception
{
    public static function failed($path, $destination)
    {
        return new static("Failed to move file `{$path}` to `{$destination}`");
    }
}
