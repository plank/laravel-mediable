<?php

return [
    /*
     * FQCN of the model to use for media
     */
    'model' => \Frasmage\Mediable\Media::class,

    /*
     * Filesystem disk to use if none is specified
     */
    'default_disk' => 'public',

    /*
     * Filesystems that can be used for media storage
     */
    'allowed_disks' => [
        'public',
    ],

    /*
     * The maximum file size in bytes for a single uploaded file
     */
    'max_size' => 1024 * 1024 * 10,

    /*
     * What to do if a duplicate file is uploaded. Options include:
     *
     * * 'increment': the new file's name is given an incrementing suffix
     * * 'replace' : the old file and media model is deleted
     * * 'error': an Exception is thrown
     *
     */
    'on_duplicate' => 'increment',

    /*
     * Reject files unless both their mime and extension are recognized and match
     */
    'strict_type_checking' => false,

    /*
     * Reject files whose mime type or extension is not recognized
     * if true, files will be given a type of `'other'`
     */
    'allow_unrecognized_types' => false,

    /**
     * Global list of recognized mime types and extensions
     */
    'type_map' => [
        'mimes' => [
            \Frasmage\Mediable\Media::TYPE_IMAGE => ['image/jpeg', 'image/png', 'image/gif'],
            \Frasmage\Mediable\Media::TYPE_IMAGE_VECTOR => ['image/svg+xml'],
            \Frasmage\Mediable\Media::TYPE_PDF => ['application/pdf'],
            \Frasmage\Mediable\Media::TYPE_AUDIO => ['audio/aac', 'audio/ogg', 'audio/mpeg', 'audio/mp3', 'audio/mpeg', 'audio/wav'],
            \Frasmage\Mediable\Media::TYPE_VIDEO => ['video/mp4', 'video/ogg', 'video/webm'],
            \Frasmage\Mediable\Media::TYPE_ARCHIVE => ['application/zip'],
            \Frasmage\Mediable\Media::TYPE_DOCUMENT => [],
        ],
        'extensions' => [
            \Frasmage\Mediable\Media::TYPE_IMAGE => ['jpg', 'jpeg', 'png', 'gif'],
            \Frasmage\Mediable\Media::TYPE_IMAGE_VECTOR => ['svg'],
            \Frasmage\Mediable\Media::TYPE_PDF => ['pdf'],
            \Frasmage\Mediable\Media::TYPE_AUDIO => ['aac', 'ogg', 'oga', 'mp3', 'wav'],
            \Frasmage\Mediable\Media::TYPE_VIDEO => ['mp4', 'm4v', 'mov', 'ogv', 'webm'],
            \Frasmage\Mediable\Media::TYPE_ARCHIVE => ['zip'],
            \Frasmage\Mediable\Media::TYPE_DOCUMENT => [],
        ]
    ],
];
