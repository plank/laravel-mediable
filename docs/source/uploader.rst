Uploading Files
============================================

.. highlight:: php

The easiest way to upload media to your server is with the ``MediaUploader`` class, which handles validating the file, moving it to its destination and creating a ``Media`` record to reference it. You can get an instance of the MediaUploader using the Facade and configure it with a fluent interface.

To upload a file to the root of the default disk (set in ``config/mediable.php``), all you need to do is the following:
::

    <?php
    use MediaUploader; //use the facade
    $media = MediaUploader::fromSource($request->file('thumbnail'))->upload();


The ``fromSource()`` method will accept either

- an instance of ``Symfony\Component\HttpFoundation\File``.
- an instance of ``Symfony\Component\HttpFoundation\UploadedFile``.
- a URL as a string, beginning with ``http://`` or ``https://``.
- an absolute path as a string, beginning with ``/``.

Specifying Destination
----------------------

You can customize where the uploader will put the file on your server before you invoke the ``upload()`` method.

::

    <?php
    $uploader = MediaUploader::fromSource($request->file('thumbnail'))

    // specify a disk to use instead of the default
    ->setDisk('s3');

    // place the file in a directory relative to the disk root
    ->setDirectory('user/john/profile')

    // alternatively, specify both the disk and directory at once
    ->toDestination('s3', 'user/john/profile')

    // Overide the filename of the source file
    ->setFilename('profile.jpg')

    ->upload();


Validation
--------------------

The ``MediaUpload`` will perform a number of validation checks on the source file. If any of the checks fail, a ``Plank\Mediable\MediaUploaderException`` will be through with a message indicating why the file was rejected.


You can override the most validation configuration values set in ``config/mediable.php`` on a case-by-case basis using the same fluent interface.

::

    <?php
    $media = MediaUploader::fromSource($request->file('image'))

        // model class to use
        ->setModelClass(MediaSubclass::class)

        // maximum filesize in bytes
        ->setMaximumSize(99999)

        // how to handle a file that already exists at the destination
        ->setOnDuplicateBehavior(Media::ON_DUPLICATE_REPLACE)

        // whether the aggregate type must match both the MIME type and extension
        ->setStrictTypeChecking(true)

        // whether to allow the 'other' aggregate type
        ->setAllowUnrecognizedTypes(true)

        // only allow files of specific MIME types
        ->setAllowedMimeTypes(['image/jpeg'])

        // only allow files of specifc extensions
        ->setAllowedExtensions(['jpg', 'jpeg'])

        // only allow files of specific aggregate types
        ->setAllowedAggregateTypes(['image'])

        ->upload();
