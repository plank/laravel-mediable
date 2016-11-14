<?php

namespace Plank\Mediable\Exceptions\MediaUpload;

use Plank\Mediable\Exceptions\MediaUploadException;

class ForbiddenException extends MediaUploadException
{
    public static function diskNotAllowed($disk)
    {
        return new static("The disk `{$disk}` is not in the allowed disks for media.");
    }
}
