<?php

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class InvalidHashException extends MediaUploadException
{
    public static function hashMismatch(string $expectedhash, string $actualHash): self
    {
        return new static("File's md5 hash `{$actualHash}` does not match expected `{$expectedhash}`.");
    }
}
