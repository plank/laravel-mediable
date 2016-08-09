<?php

namespace Plank\Mediable\Exceptions;

use Exception;

/**
 * @author Sean Fraser <sean@plankdesign.com>
 */
class MediaMoveException extends Exception
{
    public static function destinationExists($path)
    {
        return new static("Another file already exists at `{$path}`");
    }
}
