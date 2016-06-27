<?php

namespace Frasmage\Mediable\Exceptions;

use \Exception;

class MediaMoveException extends Exception
{
    public static function cannotChangeExtension($original, $target)
    {
        return new static("Cannot change file extension from `{$original}` to `{$target}`.");
    }

    public static function destinationExists($path)
    {
        return new static("Another file already exists at `{$path}`");
    }
}
