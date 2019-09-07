<?php
declare(strict_types=1);

namespace Plank\Mediable\Exceptions;

use Exception;

class MediaMoveException extends Exception
{
    public static function destinationExists(string $path): self
    {
        return new static("Another file already exists at `{$path}`");
    }

    public static function failedToCopy(string $from, string $to): self
    {
        return new static("Failed to copy file from `{$from}` to `{$to}`.");
    }
}
