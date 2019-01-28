<?php

namespace Plank\Mediable;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for Media Uploader.
 * @author Sean Fraser <sean@plankdesign.com>
 *
 * @method $this fromSource(mixed $source)
 * @method $this fromString(string $source)
 * @method $this toDestination($disk, $directory)
 * @method $this toDisk($disk)
 * @method $this toDirectory($directory)
 * @method $this useFilename($filename)
 * @method $this useHashForFilename()
 * @method $this useOriginalFilename()
 * @method $this setModelClass($class)
 * @method $this setMaximumSize($size)
 * @method $this setOnDuplicateBehavior($behavior)
 * @method string getOnDuplicateBehavior()
 * @method $this onDuplicateError()
 * @method $this onDuplicateIncrement()
 * @method $this onDuplicateReplace()
 * @method $this setStrictTypeChecking($strict)
 * @method $this setAllowUnrecognizedTypes($allow)
 * @method $this setTypeDefinition($type, $mime_types, $extensions)
 * @method $this setAllowedMimeTypes($allowed_mimes)
 * @method $this setAllowedExtensions($allowed_extensions)
 * @method $this setAllowedAggregateTypes($allowed_types)
 * @method string inferAggregateType($mime_type, $extension)
 * @method string[] possibleAggregateTypesForMimeType($mime)
 * @method string[] possibleAggregateTypesForExtension($extension)
 * @method Media upload()
 * @method $this beforeSave(callable $callable)
 * @method Media importPath($disk, $path)
 * @method Media import($disk, $directory, $filename, $extension)
 * @method bool update(Media $media)
 * @method void verifyFile()
 */
class MediaUploaderFacade extends Facade
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
