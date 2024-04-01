<?php

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class InvalidHashException extends MediaUploadException
{
    public static function hashMismatch(string $algo, string $expectedhash, string $actualHash): self
    {
        return new static("File's $algo hash `{$actualHash}` does not match expected `{$expectedhash}`.");
    }
}
