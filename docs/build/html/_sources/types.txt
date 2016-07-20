Aggregate Types
===============

Laravel-Mediable provides functionality for handling multiple kinds of files under a shared aggregate type. This is intended to make it easy to find similar media without needing to constantly juggle multiple MIME types or file extensions. For example, you might want to query for an image, but not care if it is in JPEG, PNG or GIF format.

::

    Media::where('aggregate_type', Media::TYPE_IMAGE)->get();


You can use this functionality to restrict the uploader to only accept certain types of files

::

    MediaUploader::fromSource($request->file('thumbnail'))
        ->toDestination('uploads', '')
        ->setAllowedAggregateTypes([Media::TYPE_IMAGE, Media::TYPE_IMAGE_VECTOR])
        ->upload()

To customize the aggregate type definitions for your project, see :ref:`Configuring Aggregate Types <aggregate_types>`
