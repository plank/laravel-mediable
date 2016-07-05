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
composer require frasmage/laravel-mediable
```

Register the package's servive provider in `config/app.php`

```php
'providers' => [
    ...
    'Frasmage\Mediable\MediableServiceProvider',
    ...
];
```

The package comes with a Facade for the image uploader, which you can optionally register as well.

```php
'aliases' => [
	...
    'MediaUploader' => 'Frasmage\Mediable\MediaUploaderFacade',
    ...
]
```

Publish the config file (`config/mediable.php`) and migration file (`database/migrations/####_##_##_######_create_mediable_tables.php`) of the package using artisan.

```bash
php artisan vendor:publish --provider="Frasmage\Mediable\MediableServiceProvider"
```

Run the migrations to add the required tables to your database.

```bash
php artisan migrate
```

## Uploading Files

The easiest way to upload media to your server is with the `MediaUploader` class, which handles validating the file, moving it to its destination and creating a `Media` instance to reference it. You can get an instance of the MediaUploader using the Facade and configure it with a fluent interface.

```php
$media = MediaUploader::fromSource($request->file('thumbnail'))
	->toDestination('uploads', '/') // root of the uploads disk
	->withFilename('thumbnail') // override the source's filename
	->upload();
```

The `fromSource()` method will accept either

- an instance of `Symfony\Component\HttpFoundation\File`
- an instance of `Symfony\Component\HttpFoundation\UploadedFile`
- a URL as a string.
- an absolute path as a string.

You can override the most configuration values that apply to the uploader on a case-by-case basis using the same fluent interface.

```php
$media = MediaUploader::fromSource($request->file('image'))
	->toDestination('uploads', '/')
	->setModelClass(MediSubclass::class)
	->setMaximumSize(99999)
	->setOnDuplicateBehavior(Media::ON_DUPLICATE_REPLACE)
	->setStrictTypeChecking(true)
	->setAllowUnrecognizedTypes(true)
	->setAllowedMimeTypes(['image/jpeg'])
	->setAllowedExtensions(['jpg', 'jpeg'])
	->setAllowedMediaTypes(['image'])
	->upload();
```


## Handling Media

Add the `Mediable` trait to any Eloquent models that you would like to be able to attach media to.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Frasmage\Mediable\Mediable;

class Post extends Model
{
	use Mediable;

	// ...
}
```

### Attaching Media

You can attach media to your model using the `attachMedia()` method. This method takes a second argument, specifying one or more tags which define the relationship between the model and the media.

```php
$post = Post::first();
$post->attachMedia($media, 'thumbnail');
```

You can attach multiple media to the same tag with a single call. The `attachMedia()` method accept any of the following for its first parameter:

- a numeric or string id
- an instance of `Media`
- an array of ids
- an instance of `\Illuminate\Database\Eloquent\Collection`

```php
$post->attachMedia([$media1->id, $media2->id], 'gallery');
```

You can also assign media to multiple tags with a single call.

```php
$post->attachMedia($media, ['gallery', 'featured']);
```

### Replacing Media

Media and Mediable models share a many-to-many relationship, which allows for any number of media to be added to any key. The `attachMedia()` method will add a new association, but will not remove any existing associations to other media. If you want to replace the media previously attached to the specified tag(s) you can use the `syncMedia()` method. This method accepts the same inputs as `attachMedia()`.

```php
$post->syncMedia($media, 'thumbnail');
```

### Retrieving Media

You can retrieve media attached to a file by refering to the tag to which it was previously assigned.

```php
$media = $post->getMedia('thumbnail');
```

This returns a collection of all media assigned to that tag. In cases where you only need one `Media` entity, you can instead use `firstMedia()`.

```php
$media = $post->firstMedia('thumbnail');
// shorthand for
$media = $post->getMedia('thumbnail')->first();
```

If you specify an array of tags, the method will return media is attached to any of those tags. Set the `$match_all` parameter to `true` to tell the method to only return media that are attached to all of the specified tags.

```php
$post->getMedia(['header', 'footer']); // get media with either tag
$post->getMedia(['header', 'footer'], true); //get media with both tags
$post->getMediaMatchAll(['header', 'footer']); //alias
```

You can also get all media attached to a model, grouped by tag.

```php
$post->getAllMediaByTag();
```

### Checking for the Presence of Media

You can verify if a model has one or more media assigned to a given tag with the `hasMedia()` method.

```php
if($post->hasMedia('thumbnail')){
	// ...
}
```

You can specify multiple tags when calling either method, which functions similarly to `getMedia()`. The method will return `true` if `getMedia()` passed the same parameters would return any instances.

You also can also perform this check using the query builder.

```php
$posts = Post::whereHasMedia('thumbnail')->get();
```

### Detaching Media

 You can remove a media instance from a model with the `detachMedia()` method.

```php
$post->detachMedia($media); // remove media from all tags
$post->detachMedia($media, 'feature'); //remove media from specific tag
$post->detachMedia($media, ['feature', 'thumbnail]); //remove media from specific tags
```

 You can also remove all media assigned to one or more tags

```php
$post->detachMediaTags('feature');
$post->detachMediaTags(['feature', 'thumbnail']);
```


## Using Media

### Media Paths & URLs

`Media` instances keep track of the location of their file and are able to generate a number of paths and URLs relative to the file. Consider the following example:

*config/filesystems.php*

```php
'disks' => [
	'uploads' => [
		'driver' => 'local',
		'root' => public_path('uploads')
	]
],
```

*given a `Media` instance with the following attributes*

```

[
	'disk' => 'uploads',
	'directory' => 'foo/bar',
	'filename' => 'picture',
	'extension' => 'jpg'
	// ...
];
```

The following attributes and methods would be exposed

```php
$media->absolutePath();
# /var/www/site/public/uploads/foo/bar/picture.jpg

$media->dirname;
# /var/www/site/public/uploads/foo/bar

$media->diskPath();
# foo/bar/picture.jpg

$media->directory;
# foo/bar

$media->basename;
# picture.jpg

$media->filename;
# picture

$media->extension;
# jpg
```

#### Public Paths

If the file is located below the webroot, the following methods are also available:

```php
$media->publicPath();
# /uploads/foo/bar/picture.jpg

$media->url();
# http://localhost/uploads/foo/bar/picture.jpg
```

You can check if a media instance's file is located below the webroot with

```php
$media->isPubliclyAccessible();
```

#### Glide Integration

If the [spatie/laravel-glide](https://github.com/spatie/laravel-glide) package is installed and a file is below Glide's source directory, the following helper methods are also available.

*example assumes Glide source is set to `public_path('uploads')`*

```php

$media->glidePath() //path relative to the glide source root
# /foo/bar/picture.jpg

$media->glideUrl(['w' => 400]); //generate glide image
# http://localhost/glide/foo/bar/picture.jpg?w=400&s=...
```

You can check if a media instance's file is located below Glide's source directory with

```php
$media->isGlideAccessible();
```

### Querying Media

If you need to query the media table directly, rather than through associated models, the Media class exposes a few helpful methods for the query builder.

```php
Media::inDirectory($disk, $directory, $recursive = false);
Media::inOrUnderDirectory($disk, $directory);
Media::whereBasename($basename);
Media::forPathOnDisk($disk, $path);
```


### Moving Media

You should taking caution if manually changing a media instance's attributes, as you record and file could go out of sync.

You can change the location of a media file on disk. You cannot move a media to a different disk this way.

```php
$media->move('new/directory');
$media->move('new/directory', 'new-filename');
$media->rename('new-filename');
```

### Deleting Media

You can delete media with standard Eloquent model `delete()` method. This will also delete the file associated with the instance.

```php
$media->delete();
```

**Note**: The delete method on the query builder *will not* delete the associated file.

```php
Media::where(...)->delete(); //will not delete files
```
