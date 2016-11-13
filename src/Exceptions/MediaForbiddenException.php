<?php

namespace Plank\Mediable\Exceptions;

class MediaForbiddenException extends MediaUploadException
{
    public static function diskNotAllowed($disk)
    {
        return new static("The disk `{$disk}` is not in the allowed disks for media.");
    }
}
