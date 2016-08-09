.. Laravel-Mediable documentation master file, created by
   sphinx-quickstart on Tue Jul 19 14:49:33 2016.
   You can adapt this file completely to your liking, but it should at least
   contain the root `toctree` directive.

Plank/Laravel-Mediable
============================================

.. image:: https://travis-ci.org/plank/laravel-mediable.svg?branch=master
    :target: https://travis-ci.org/plank/laravel-mediable
    :alt: Build Status
.. image:: https://coveralls.io/repos/github/plank/laravel-mediable/badge.svg?branch=master
    :target: https://coveralls.io/github/plank/laravel-mediable
    :alt: Coverage Status
.. image:: https://insight.sensiolabs.com/projects/0eaf2725-64f4-4494-ae61-ca3961ba50c5/mini.png
    :target: https://insight.sensiolabs.com/projects/0eaf2725-64f4-4494-ae61-ca3961ba50c5
    :alt: SensioLabsInsight
.. image:: https://styleci.io/repos/63791110/shield
    :target: https://styleci.io/repos/63791110
    :alt: StyleCI
.. image:: https://img.shields.io/packagist/v/plank/laravel-mediable.svg
    :target: https://packagist.org/packages/plank/laravel-mediable
    :alt: Packagist

Laravel-Mediable is a package for easily uploading and attaching media files to models with Laravel 5.

Features
-------------

* Filesystem-driven approach is easily configurable to allow any number of upload directories with different accessibility.
* Many-to-many polymorphic relationships allow any number of media to be assigned to any number of other models without any need to modify the schema.
* Attach media to models with tags, to set and retrieve media for specific purposes, such as ``'thumbnail'``, ``'featured image'``, ``'gallery'`` or ``'download'``.
* Easily query media and restrict uploads by MIME type, extension and/or aggregate type (e.g. ``image`` for jpeg, png or gif).


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
