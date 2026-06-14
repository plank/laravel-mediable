<?php

namespace Plank\Mediable\Enum;

enum OnDuplicateBehaviour: string
{
    /**
     * When a duplicate file upload is detected, the old file will be overwritten with the new file,
     * and the existing media record will be updated with the new file's metadata.
     */
    case Update = 'update';

    /**
     * When a duplicate file upload is detected, the new file's name will append an incrementing number
     * (e.g., "file.jpg" becomes "file-1.jpg", "file-2.jpg", etc.) until a unique name is found.
     * This is the default behaviour.
     */
    case Increment = 'increment';

    /**
     * When a duplicate file upload is detected, an exception will be thrown
     */
    case Error = 'error';

    /**
     * When a duplicate file upload is detected, the existing media record will be deleted along with its file
     * And a new media record will be created for the new file.
     */
    case Replace = 'replace';

    /**
     * When a duplicate file upload is detected, the existing media record will be deleted along with its file and all variat
     * And new media records will be created for the new file.
     */
    case ReplaceWithVariants = 'replace_with_variants';
}
