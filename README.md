# Laravel-Mediable

[![Travis](https://img.shields.io/travis/plank/laravel-mediable/master.svg?style=flat-square)](https://travis-ci.org/plank/laravel-mediable)
[![Coveralls](https://img.shields.io/coveralls/plank/laravel-mediable.svg?style=flat-square)](https://coveralls.io/github/plank/laravel-mediable)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/0eaf2725-64f4-4494-ae61-ca3961ba50c5.svg?style=flat-square)](https://insight.sensiolabs.com/projects/0eaf2725-64f4-4494-ae61-ca3961ba50c5)
[![StyleCI](https://styleci.io/repos/63791110/shield)](https://styleci.io/repos/63791110)
[![Packagist](https://img.shields.io/packagist/v/plank/laravel-mediable.svg?style=flat-square)](https://packagist.org/packages/plank/laravel-mediable)

Laravel-Mediable is a package for easily uploading and attaching media files to models with Laravel 5.

## Features

- Filesystem-driven approach is easily configurable to allow any number of upload directories with different accessibility.
- Many-to-many polymorphic relationships allow any number of media to be assigned to any number of other models without any need to modify the schema.
- Attach media to models with tags, to set and retrieve media for specific purposes, such as `'thumbnail'`, `'featured image'`, `'gallery'` or `'download'`.
- Easily query media and restrict uploads by MIME type, extension and/or aggregate type (e.g. `image` for jpeg, png or gif).

## Example Usage

Upload a file to the server, and place it in a directory on the filesystem disk named "uploads". This will create a Media record that can be used to refer to the file.

```php
$media = MediaUploader::fromSource($request->file('thumb'))
	->toDestination('uploads', 'blog/thumbnails')
	->upload();
```

Attach the Media to another eloquent model with one or more tags defining their relationship.

```php
$post = Post::create($this->request->input());
$post->attachMedia($media, ['thumbnail']);
```

Retrieve the media from the model by its tag(s).

```php
$post->getMedia('thumbnail')->first()->getUrl();
```

## Installation

Add the package to your Laravel app using composer

```bash
composer require plank/laravel-mediable
```

Register the package's servive provider in `config/app.php`

```php
'providers' => [
    ...
    Plank\Mediable\MediableServiceProvider::class,
    ...
];
```

The package comes with a Facade for the image uploader, which you can optionally register as well.

```php
'aliases' => [
	...
    'MediaUploader' => Plank\Mediable\MediaUploaderFacade::class,
    ...
]
```

Publish the config file (`config/mediable.php`) and migration file (`database/migrations/####_##_##_######_create_mediable_tables.php`) of the package using artisan.

```bash
php artisan vendor:publish --provider="Plank\Mediable\MediableServiceProvider"
```

Run the migrations to add the required tables to your database.

```bash
php artisan migrate
```

## Documentation

Read the documentation [here](http://laravel-mediable.readthedocs.io/en/latest/).

## License

This package is released under the MIT license (MIT).

## About Plank

[Plank](http://plankdesign.com) is a web development agency based in Montreal, Canada.

