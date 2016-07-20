Uploading Files
============================================

The easiest way to upload media to your server is with the ``MediaUploader`` class, which handles validating the file, moving it to its destination and creating a ``Media`` record to reference it. You can get an instance of the MediaUploader using the Facade and configure it with a fluent interface.

.. highlight:: php

::

    <?php
    //provide the source file
    $media = MediaUploader::fromSource($request->file('thumbnail'))
        //specify which disk to upload the file to, and where on the disk to put it
        ->toDestination('uploads', 'blog/thumbnails')
        // override the source's filename (optional)
        ->withFilename('my-thumbnail')
        //perform the file upload
        ->upload();


The ``fromSource()`` method will accept either

- an instance of ``Symfony\Component\HttpFoundation\File``
- an instance of ``Symfony\Component\HttpFoundation\UploadedFile``
- a URL as a string.
- an absolute path as a string.


Validation
--------------------

The ``MediaUpload`` will perform a number of validation checks on the source file. If any of the checks fail, a ``Plank\Mediable\MediaUploaderException`` will be through with a message indicating why the file was rejected.


You can override the most validation configuration values set in ``config/mediable.php`` on a case-by-case basis using the same fluent interface.

::

    <?php
    $media = MediaUploader::fromSource($request->file('image'))
        ->toDestination('uploads', '/')
        ->setModelClass(MediaSubclass::class)
        ->setMaximumSize(99999)
        ->setOnDuplicateBehavior(Media::ON_DUPLICATE_REPLACE)
        ->setStrictTypeChecking(true)
        ->setAllowUnrecognizedTypes(true)
        ->setAllowedMimeTypes(['image/jpeg'])
        ->setAllowedExtensions(['jpg', 'jpeg'])
        ->setAllowedAggregateTypes(['image'])
        ->upload();
