# Upgrading

## 5.x to 6.x

* Minimum PHP version moved to 8.1
* Minimum Laravel version moved to 10
* New database migration file is included with the package. Run `php artisan migrate` to apply the changes.
* To add support for data URLs to the MediaUploader, the following entry should be added to the `source_adapters.pattern` field in `config/mediable.php`
  ```php
  '^data:/?/?[^,]*,' => Plank\Mediable\SourceAdapters\DataUrlAdapter::class,
  ```
* To specify default handling of inferred vs. client-provided MIME types, the following entry should be added to `config/mediable.php`. If `prefer_client_mime_type` is set to `true`, the MIME type provided by the client will be used when available. If set to `false`, the MIME type will always be inferred from the file contents. Defaults to `false`.
  ```php
  'prefer_client_mime_type' => false,
  ```
* All properties now declare their types if able, and a handful of missing method return types have been added. If extending any class or implementing any interface from this package, property types may need to be updated.
* If you have implemented a custom SourceAdapter, you will need to apply the following changes from the `SourceAdapterInterface` interface:
  * Implement the `getStream(): StreamInterface` method.
  * Implement the `getHash(string $algo): string` method.
  * he return type of the `filename()` and `extension()` method is now nullable. If the adapter cannot determine the value from the information available, it should return null.
  * Remove the `getContents()` method. The `getStream()->getContents()` method may be used instead.
  * Remove the `getSource()` method. No replacement.
  * Remove the `path()` method. No replacement.
  * Remove the `valid()` method. SourceAdapters should now throw an exception with a more helpful message from the constructor if the source is not valid.
* The `Plank\Mediable\Stream` class has been removed in favor of the `guzzlehttp/psr7` implementation. If you were using this class directly, you will need use another PSR-7 compatible stream wrapper instead (such as Guzzle's).
* To make use of the image optimization feature:
  * Install the necessary binaries for the types of images that you are working with. See [spatie/image-optimizer documentation](https://github.com/spatie/image-optimizer/blob/main/README.md#optimization-tools) for installation instructions on various operating systems.
  * add the `image_optimization.enabled` and `image_optimization.optimizers` configs to the `config/mediable.php` file. See the [sample configuration file](https://github.com/plank/laravel-mediable/blob/master/config/mediable.php) for a recommended baseline setup.
* The `ImageManipulation::usingHashForFilename()` method has been renamed to `ImageManipulation::isUsingHashForFilename()` to avoid confusion with the `useHashForFilename()` method.
* `\Plank\Mediable\HandlesMediaUploadExceptions::transformMediaUploadException()` parameter and return type changed from `\Exception` to `\Throwable`.

## 4.x to 5.x

* Database migration files are now served from within the package. In your migrations table, rename the `XXXX_XX_XX_XXXXXX_create_mediable_tables.php` entry to `2016_06_27_000000_create_mediable_tables.php` and delete your local copy of the migration file from the /database/migrations directory. If any customizations were made to the tables, those should be defined as one or more separate ALTER table migrations.
* Two columns added to the `media` table: `variant_name` (varchar)  and `original_media_id` (should match `media.id` column type). Migration file is included with the package.
* `Plank\Mediable\MediaUploaderFacade` moved to `Plank\Mediable\Facades\MediaUploader`
* Directory and filename validation now only allows URL and filesystem safe ASCII characters (alphanumeric plus `.`, `-`, `_`, and `/` for directories). Will automatically attempt to transliterate UTF-8 accented characters and ligatures into their ASCII equivalent, all other characters will be converted to hyphens.
* The following methods now include an extra `$withVariants` parameter :
    * `Mediable::scopeWithMedia()`
    * `Mediable::scopeWithMediaMatchAll()`
    * `Mediable::loadMedia()`
    * `Mediable::loadMediaMatchAll()`
    * `MediableCollection::loadMedia()`
    * `MediableCollection::loadMediaMatchAll()`

## 3.x to 4.x

* UrlGenerators no longer throw `MediaUrlException` when the file does not have public visibility. This removes the need to read IO for files local disks or to make HTTP calls for files on s3 disks. Visibility can still checked with `$media->isPubliclyAccessible()`, if necessary.
* Highly recommended to explicitly specify the `'url'` config value on all disks used to generate URLs.
* No longer reading the `'prefix'` config of local disks. Value should be included in the `'url'` config instead.

## 2.x to 3.x

* Minimum PHP version moved to 7.2
* Minimum Laravel version moved to 5.6
* All methods now have parameter and return type hints. If extending any class or implementing any interface from this package, method signatures will need to be updated.

## 1.x to 2.x

You need to add an order column to the mediables table.

```php
$table->integer('order')->unsigned()->index();
```

A handful of methods have been renamed on the `MediaUploader` class.

`setFilename` -> `useFilename`
`setDisk` -> `toDisk`
`setDirectory` -> `toDirectory`

