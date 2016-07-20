# Laravel-Mediable

Laravel-Mediable is a package for easily uploading and attaching media files to models with Laravel 5.

## Features

- Filesystem-driven approach is easily configurable to allow any number of upload directories with different accessibility.
- Many-to-many polymorphic relationships allow any number of media to be assigned to any number of other models without any need to modify the schema.
- Attach media to models with tags, to set and retrieve media for specific purposes, such as `'thumbnail'`, `'featured image'`, `'gallery'` or `'download'`.
- Easily query media and restrict uploads by MIME type, extension and/or aggregate type (e.g. `image` for jpeg, png or gif).

## Installation

Add the package to your Laravel app using composer

```bash
composer require plank/laravel-mediable
```

Register the package's servive provider in `config/app.php`

```php
'providers' => [
    ...
    'Plank\Mediable\MediableServiceProvider',
    ...
];
```

The package comes with a Facade for the image uploader, which you can optionally register as well.

```php
'aliases' => [
	...
    'MediaUploader' => 'Plank\Mediable\MediaUploaderFacade',
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

Read the documentation [here]().

## License

This package is released under the MIT license (MIT).

## About Plank

[Plank](http://plankdesign.com) is a web development agency based in Montreal, Canada.

