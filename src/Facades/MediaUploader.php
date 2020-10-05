<?php
declare(strict_types=1);

namespace Plank\Mediable\Facades;

use Illuminate\Support\Facades\Facade;
use Plank\Mediable\Media;
use Plank\Mediable\MediaUploader as Uploader;

/**
 * Facade for Media Uploader.
 *
 * @method static Uploader fromSource(mixed $source)
 * @method static Uploader fromString(string $source)
 * @method static Uploader toDestination($disk, $directory)
 * @method static Uploader toDisk($disk)
 * @method static Uploader toDirectory($directory)
 * @method static Uploader useFilename($filename)
 * @method static Uploader useHashForFilename()
 * @method static Uploader useOriginalFilename()
 * @method static Uploader setModelClass($class)
 * @method static Uploader setMaximumSize($size)
 * @method static Uploader setOnDuplicateBehavior($behavior)
 * @method static string getOnDuplicateBehavior()
 * @method static Uploader onDuplicateError()
 * @method static Uploader onDuplicateIncrement()
 * @method static Uploader onDuplicateReplace()
 * @method static Uploader onDuplicateUpdate()
 * @method static Uploader setStrictTypeChecking($strict)
 * @method static Uploader setAllowUnrecognizedTypes($allow)
 * @method static Uploader setTypeDefinition($type, $mime_types, $extensions)
 * @method static Uploader setAllowedMimeTypes($allowed_mimes)
 * @method static Uploader setAllowedExtensions($allowed_extensions)
 * @method static Uploader setAllowedAggregateTypes($allowed_types)
 * @method static string inferAggregateType($mime_type, $extension)
 * @method static string[] possibleAggregateTypesForMimeType($mime)
 * @method static string[] possibleAggregateTypesForExtension($extension)
 * @method static Media upload()
 * @method static Media replace(Media $media)
 * @method static Uploader beforeSave(callable $callable)
 * @method static Media importPath($disk, $path)
 * @method static Media import($disk, $directory, $filename, $extension)
 * @method static bool update(Media $media)
 * @method static void verifyFile()
 */
class MediaUploader extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mediable.uploader';
    }
}
