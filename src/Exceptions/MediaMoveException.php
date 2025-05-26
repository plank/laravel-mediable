<?php

declare(strict_types=1);

namespace Plank\Mediable\Exceptions;

use Exception;

class MediaMoveException extends Exception
{
    public static function destinationExists(string $path): self
    {
        return new self("Another file already exists at `{$path}`.");
    }

    public static function destinationExistsOnDisk(string $disk, string $path): self
    {
        return new self("Another file already exists at `{$path}` on disk `{$disk}`.");
    }

    public static function fileNotFound(string $disk, string $path, ?Exception $previous = null): self
    {
        return new self("File not found at `{$path}` on disk `{$disk}`.", 0, $previous);
    }

    public static function failedToCopy(string $from, string $to, ?Exception $previous = null): self
    {
        return new self("Failed to copy file from `{$from}` to `{$to}`.", 0, $previous);
    }
}
