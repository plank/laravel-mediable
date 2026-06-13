<?php
declare(strict_types=1);

namespace Plank\Mediable\Exceptions;

use Exception;

class MediaUploadException extends Exception
{
    public static function failedToSanitize(string $message, ?Exception $original = null): self
    {
        return new self("Failed to sanitize file: " . $message, 0, $original);
    }
}
