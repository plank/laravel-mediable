<?php

namespace Frasmage\Mediable\Exceptions;

use \Exception;

class MediaMoveException extends Exception
{
    public static function destinationExists($path)
    {
        return new static("Another file already exists at `{$path}`");
    }
}
