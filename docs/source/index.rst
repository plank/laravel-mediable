.. Laravel-Mediable documentation master file, created by
   sphinx-quickstart on Tue Jul 19 14:49:33 2016.
   You can adapt this file completely to your liking, but it should at least
   contain the root `toctree` directive.

Plank/Laravel-Mediable
============================================

Laravel-Mediable is a package for easily uploading and attaching media files to models with Laravel 5.

Features
-------------

* Filesystem-driven approach is easily configurable to allow any number of upload directories with different accessibility.
* Many-to-many polymorphic relationships allow any number of media to be assigned to any number of other models without any need to modify the schema.
* Attach media to models with tags, to set and retrieve media for specific purposes, such as `'thumbnail'`, `'featured image'`, `'gallery'` or `'download'`.
* Easily query media and restrict uploads by MIME type, extension and/or aggregate type (e.g. `image` for jpeg, png or gif).


.. toctree::
   :maxdepth: 2
   :caption: Getting Started

   installation
   configuration

.. toctree::
   :maxdepth: 2
   :caption: Guides

   uploader
   mediable
   media
   types
   commands
